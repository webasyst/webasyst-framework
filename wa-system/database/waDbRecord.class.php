<?php

/**
  * Instances of this class represent database records.
  * Can be subclassed for complicated cases, e.g. with attached files.
  *
  * // representation of an existing row by id
  * $r = new waDbRecord(new appSomeModel(), $id);
  *
  * // read info from database
  * // (read is lazy: no queries is performed at constructor time)
  * echo $r->field;
  *
  * // update existing database row
  * $r->field = 'new value';
  * $r->save();
  *
  * // create new database row
  * $r = new waDbRecord(new appSomeModel());
  * $r->field = 'value';
  * $new_id = $r->save();
  *
  * You may use array access instead of field access:
  * $r->field is the same as $r['field']
  */
class waDbRecord extends waArrayObjectDiff
{
    /** Model to use for saving and loading data.
      * @var waModel */
    protected $m;

    /** @var string|int|null id of a row as accepted by model */
    protected $id = null;

    //
    // Public interface
    //

    /**
     * @param waModel $m model to use for saving
     * @param mixed $id id of existing record, as accepted by model; omit to create new record.
     */
    public function __construct(waModel $m, $id = null)
    {
        parent::__construct();
        $this->m = $m;

        $table_id_field = $this->m->getTableId();
        if (!$table_id_field || !is_string($table_id_field)) {
            throw new waException('waDbRecord requires primary key and only supports simple (non-composite) primary keys');
        }

        if ($id) {
            $this->id = $id;
            $this->persistent[$table_id_field] = $id;
        } else {
            $this->setAll($this->getDefaultValues());
        }
    }

    /**
     * Id of current record as was passed to constructor.
     *
     * Note that when ->exists() finds out that record does not exist in DB, it
     * sets internal id to null and never tries to access DB again.
     * $this['id'] and $this->id are still accessable though and allow to create record with any id.
     *
     * @return string|int|null id of the record
     */
    public function getId()
    {
        return $this->id;
    }

    /**
      * Save this record to database: insert or update depending on whether record exists in DB.
      * Silently ignores all keys that have no corresponding fields in database table.
      *
      * @return mixed this record id
      * @throws waException when values fail validation
      */
    public function save()
    {
        $this->beforeSave();
        $values = array_intersect_key($this->removeStubs()->rec_data, $this->m->getMetadata());

        $do_insert = true;
        $id = $this->getId();

        // Force saving with given id, if specified during construction
        $id || $id = $this->persistent->ifset($this->m->getTableId());

        // Update if record exists
        if ($id && $this->exists()) {
            if (!$values) {
                $do_insert = false;
            } else {
                $result = $this->m->updateById($this->id, $values, null, true);
                if ($result->affectedRows()) {
                    $do_insert = false;
                    if (!empty($values[$this->m->getTableId()])) {
                        $this->id = $values[$this->m->getTableId()];
                    }
                } else {
                    // Make sure the record exists
                    if ( ( $row = $this->m->getById($this->id))) {
                        $do_insert = false;
                        $this->persistent->setAll($row);
                    } else {
                        $this->clearPersistent();
                    }
                }
            }
        } else if ($id && empty($values[$this->m->getTableId()])) {
            // id was given to constructor, but no such record exists
            $values[$this->m->getTableId()] = $id;
        }

        // No row in database yet: insert
        if ($do_insert) {
            unset($this->persistent[$this->m->getTableId()]);
            $this->id = $this->m->insert($values);
            $this[$this->m->getTableId()] = $this->id;
        }

        $this->afterSave();
        $this->merge();
        return $this->id;
    }

    /**
     * Load data if not loaded yet.
     *
     * Called lazily when array or field access is used.
     *
     * When $field_or_db_row is an array then it is used as data source and no database queries are made.
     * Array structure is the same as returned by $this->toArray().
     *
     * When $field_or_db_row is a field name (string) then this function ensures that this field is loaded
     * (when possible). It's not guaranteed to load the rest of the fields.
     * This gets called lazily when field/array access interface is used.
     *
     * When $field_or_db_row is empty, all available data is fetched from database using id given to a constructor.
     *
     * @param mixed $field_or_db_row
     * @throws waException
     * @return $this
     */
    public function load($field_or_db_row = null)
    {
        if ($this->id === null) {
            throw new waException('Unable to load data from '.$this->m->getTableName(), 404);
        }

        // already loaded?
        if (is_string($field_or_db_row) && isset($this->persistent->rec_data[$field_or_db_row])) {
            return $this;
        }

        // let subclasses populate $this->persistent->rec_data
        $this->doLoad($field_or_db_row);

        // allow subclasses post-modify the data if necessary
        $this->afterLoad($field_or_db_row);
        return $this;
    }

    /**
     * Delete this record from database.
     * $this is not guaranteed to be accessable via array or field access after deletion.
     */
    public function delete()
    {
        $this->beforeDelete();
        $this->clearPersistent();
        $this->m->deleteById($this->id);
        $this->afterDelete();
        $this->id = null;
    }

    /**
     * @return bool whether this record has corresponding row in database
     */
    public function exists()
    {
        if (!$this->getId()) {
            return false;
        }

        try {
            self::doLoad();
        } catch (Exception $e) {
            $this->id = null;
            return false;
        }

        return true;
    }

    //
    // Protected functions to override in subclasses
    //

    /**
     * Generates default values for new record.
     *
     * To be overriden in subclasses.
     * Default implementation returns array(field => '') for each field from model.
     *
     * @return array same structure as $this->toArray() returns
     */
    protected function getDefaultValues()
    {
        return array_fill_keys(array_keys($this->m->getMetadata()), '');
    }

    /**
     * List of keys that are guaranteed by $this->load() to set.
     *
     * To be overriden in subclasses.
     * Default implementation uses field names from $this->m->getMetadata()
     */
    protected function getLoadableKeys()
    {
        return array_keys($this->m->getMetadata());
    }

    /**
      * Called by save() before writing to DB.
      *
      * Subclasses may override this method to tune save() behavior, e.g. to validate
      * and prepare $this->db_row.
      *
      * @throws waException if validation fails
      */
    protected function beforeSave()
    {
        // to be overriden in subclasses
    }

    /**
      * Called by save() after writing to DB when $this->id is already created and available.
      * $this->rec_data still contains values just written to DB.
      * When this function returns, remaining values from $this->rec_data will be moved to $this->persistent.
      *
      * Subclasses may override this method to tune save() behavior, e.g. to move attachments
      * to appropriate place using record id.
      */
    protected function afterSave()
    {
        // to be overriden in subclasses
    }

    /**
     * Called by $this->load() to populate data into $this->persistent
     *
     * Subclasses may override this method to tune load() behavior, e.g.
     * to load data lazily by field name passed in $field_or_db_row.
     * Subclasses overriding this should also call parent::doLoad() since the base class
     * uses it to load data from $this->m model.
     *
     * @param array $field_or_db_row see $this->load()
     * @throws waException
     */
    protected function doLoad($field_or_db_row = null)
    {
        // load from array?
        if (is_array($field_or_db_row)) {
            $fields = $this->m->getMetadata();
            $nulls = array_fill_keys(array_keys($fields), null);
            $this->persistent->setAll(array_intersect_key($field_or_db_row, $fields) + $nulls);
            return;
        }

        // requested field already loaded?
        if ($field_or_db_row) {
            // check if can be loaded from $this->m model
            if (!array_key_exists($field_or_db_row, $this->m->getMetadata())) {
                return;
            }
        }
        // check if something is not loaded when all fields are requested
        else {
            $loaded = true;
            foreach($this->m->getMetadata() as $f => $v) {
                if (!$this->persistent->keyExists($f)) {
                    $loaded = false;
                    break;
                }
            }
            if ($loaded) {
                return;
            }
        }

        // load from model
        $row = $this->m->getById($this->id);
        if (!$row) {
            $id = $this->id;
            $this->id = null;
            throw new waException('No record found in '.$this->m->getTableName().' for id='.htmlspecialchars($id), 404);
        }
        $this->persistent->setAll($row);
    }

    /**
     * Called by $this->load() after data has been added to $this->persistent
     * either from $field_or_db_row (if it's an array), or from $this->m model.
     *
     * Subclasses may override this method to tune load()'s behavior, e.g. to populate additional
     * data into class fields or modify data fetched from DB.
     *
     * @param array $field_or_db_row see $this->load()
     */
    protected function afterLoad($field_or_db_row = null)
    {
        // to be overriden in subclasses
    }

    /**
     * Called by $this->delete() before removing record from $this->m
     */
    protected function beforeDelete()
    {
        // to be overriden in subclasses
    }

    /**
     * Called by $this->delete() after removing record from $this->m
     */
    protected function afterDelete()
    {
        // to be overriden in subclasses
    }

    //
    // Override some access functions to support lazy loading from database
    //

    public function toArray()
    {
        if ($this->id) {
            $this->load();
        }
        return parent::toArray();
    }

    public function &__get($name)
    {
        // safe to call parent right away when key already exists in persistent data or in rec_data
        if (parent::keyExists($name) || $this->persistent->keyExists($name)) {
            return parent::__get($name);
        }

        // load the key if possible
        if ($this->id && in_array($name, $this->getLoadableKeys())) {
            $this->load($name);
        }

        return parent::__get($name);
    }

    public function __isset($name)
    {
        return parent::__isset($name) || $this->persistent->__isset($name) || in_array($name, $this->getLoadableKeys());
    }

    public function keyExists($name)
    {
        return parent::keyExists($name) || $this->persistent->keyExists($name) || in_array($name, $this->getLoadableKeys());
    }
}

