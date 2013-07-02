<?php 

class contactsAnalyticsConfig extends waAnalyticsConfig
{
	
	public function getEntities()
	{
		$fields = array();
		$app_fields = waContactFields::getAll('enabled');
		foreach ($app_fields as $id => $f) {
            /**
             * @var $f waContactField
             */
			if ($f->getStorage(true) == 'info') {
				$fields[$id] = array(
					'name' => $f->getName(),
					'table' => 'wa_contact',
					'field' => $f->getField()
				); 
				if ($id == 'name') {
					$fields[$id]['key'] = 'id';
				}
			} elseif ($f->getStorage(true) == 'email') {
				$fields[$id] = array(
					'name' => $f->getName(),
					'table' => 'wa_contact_emails',
					'field' => 'email' 
				);				
			} else {
				if ($f instanceof waContactCompositeField) {
                    /**
                     * @var $f waContactCompositeField
                     */
					foreach ($f->getFields() as $sf) {
                        /**
                         * @var $sf waContactField
                         */
                        $fields[$f->getId().":".$sf->getId()] = array(
                            'name' => $sf->getName(),
                            'table' => 'wa_contact_data',
                            'field' => 'value'
                        );
                    }
				} else {
					$fields[$id] = array(
						'name' => $f->getName(),
						'table' => 'wa_contact_data',
						'field' => 'value' 
					);
				}				
			}
		}
		$fields['count'] = array(
			'new' => true,
			'name' => 'Number of contacts',
			'select' => 'count(DISTINCT :key)'
		);
		$result = array(
			'contact' => array(
				'name' => _w('Contacts'),
				'fields' => $fields,
				'tables' => array(
					'wa_contact' => array(
						'key' => 'id'
					),
					'wa_contact_data' => array(
						'key' => 'contact_id',
						'where' => ":table.field = ':field_id'",
						'post' => true,
						'alias' => 'd'
					),
					'wa_contact_emails' => array(
						'key' => 'contact_id',
						'post' => true
					) 
				)
			)
		);
		return $result;
	}
	
	public function postData(&$result, $keys, $table_fields, $offset)
	{
		$model = new waModel();
		$key_offset = 0;
		foreach ($table_fields as $table => $fields) {
			if ($table == 'wa_contact_data') {
				$sql = 'SELECT contact_id, field, GROUP_CONCAT(DISTINCT value SEPARATOR ", ") value FROM wa_contact_data 
						WHERE 
						contact_id IN ("'.implode('","', $keys).'") 
						AND 
						field IN ("'.implode('","', $fields).'")
						GROUP BY contact_id, field';
				$data = array();
				foreach ($model->query($sql) as $row) {
					$data[$row['contact_id']][$row['field']] = $row['value'];
				}
				
				foreach ($result as $l => $row) {
					$key = $row[$key_offset];
					foreach ($fields as $index => $f) {
						$result[$l][$offset + $index] = htmlspecialchars(isset($data[$key][$f]) ? $data[$key][$f] : '<not specified>');
					}
				}
			}
			if ($table == 'wa_contact_emails') {
				$sql = 'SELECT contact_id, GROUP_CONCAT(DISTINCT email SEPARATOR ", ") value FROM wa_contact_emails 
						WHERE 
						contact_id IN ("'.implode('","', $keys).'")
						GROUP BY contact_id';
				$data = $model->query($sql)->fetchAll('contact_id', true);
				foreach ($result as $l => $row) {
					$key = $row[$key_offset];
					foreach ($fields as $index => $f) {
						$result[$l][$offset + $index] = htmlspecialchars(isset($data[$key]) ? $data[$key] : '<not specified>');
					}
				}
			}
		}
		foreach ($result as &$row) {
			array_shift($row);
		}
	}	
}