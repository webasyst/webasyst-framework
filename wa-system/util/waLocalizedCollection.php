<?php

/**
  * Read-only interface for a set of data to abstract away details of localization.
  *
  * Every item in a collection has a unique id and can be obtained 
  * by this id. Id is also returned together with the item. 
  *
  * Representation of every item in a collection may differ depending on
  * user's locale settings. Besides basic key => value and listing support,
  * this interface allows to filter items by eventual localized value. 
  */
interface waLocalizedCollection {
    /**
     * @param $id
     * @param $locale string (defaults to current system locale) - locale to return item in
     * @return one item with given id in given locale */
	public function get($id, $locale=null);
	
	/**
	 * Get the whole colelction (in stable order defined by concrete implementation).
	 * @param $start int (optional, default 0) how namy items to skip at the begining of the list
	 * @param $limit int (optional, default no limit) maximum number of items to return
	 * @param $locale string (defaults to current system locale) - locale to return items in
	 * @return array an associative array id => item 
	 */
	public function all($start=0, $limit=0, $locale=null);
	
	/**
	 * Return a part of the collection (in stable order defined by concrete implementation)
	 * @param $parameters mixed parameters to base filtering on; format is implementation-dependant
	 * @param $start int (optional, default 0) how namy items to skip at the begining of the list
	 * @param $limit int (optional, default no limit) maximum number of items to return
	 * @param $locale string (defaults to current system locale) - locale to return items in
	 * @return array an associative array id => item 
	 */
	public function filter($parameters, $start=0, $limit=0, $locale=null);
	
	/**
	 * @param $parameters (optional, default null) same parameters $this->filter() would accept
	 * @return int how many items would $this->filter() (or $this->all(), of $parameters omitted) return. */
	public function count($parameters=null);
}

// EOF