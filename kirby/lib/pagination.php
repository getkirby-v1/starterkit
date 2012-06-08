<?php

// direct access protection
if(!defined('KIRBY')) die('Direct access is not allowed');

class pagination {
  
  // the current page
  var $page = 0;
  
  // the url mode. can be 'query' or 'params' 
  var $mode = 'params';
    
  // total count of items
  var $count = 0;
  
  // the number of displayed rows
  var $limit = 0;

  // the total number of pages
  var $pages = 0;

  // the offset for the slice function
  var $offset = 0;
  
  // the range start for ranged pagination
  var $rangeStart = 0;

  // the range end for ranged pagination
  var $rangeEnd = 0;

  function __construct($data, $limit, $options=array()) {
    
    global $site;
    
    $this->pagevar = c::get('pagination.variable', 'page');
    $this->mode    = a::get($options, 'mode', c::get('pagination.method', 'params')) == 'query' ? 'query' : 'params';
    $this->data    = $data;
    $this->count   = $data->count();
    $this->limit   = $limit;
    $this->page    = ($this->mode == 'query') ? intval(get($this->pagevar)) : intval($site->uri->param($this->pagevar));
    $this->pages   = ceil($this->count / $this->limit);

    // sanitize the page
    if($this->page < 1) $this->page = 1;

    if($this->page > $this->pages && $this->count > 0) go($this->firstPageURL());

    // generate the offset
    $this->offset = ($this->page-1)*$this->limit;  
    
  }
  
  function page() {
    return $this->page;
  }
  
  function countPages() {
    return $this->pages;
  }

  function hasPages() {
    return ($this->countPages() > 1) ? true : false;
  }

  function countItems() {
    return $this->data->count();
  }

  function pageURL($page) {
    global $site;
    ($this->mode == 'query') ? $site->uri->replaceQueryKey($this->pagevar, $page) : $site->uri->replaceParam($this->pagevar, $page);
    return $site->uri->toUrl();      
  }

  function firstPage() {
    return 1;
  }

  function isFirstPage() {
    return ($this->page == $this->firstPage()) ? true : false;
  }

  function firstPageURL() {
    return $this->pageURL(1);
  }

  function lastPage() {
    return $this->pages;
  }

  function isLastPage() {
    return ($this->page == $this->lastPage()) ? true : false;
  }

  function lastPageURL() {
    return $this->pageURL($this->lastPage());
  }
  
  function prevPage() {
    return ($this->hasPrevPage()) ? $this->page-1 : $this->page;
  }
  
  function prevPageURL() {
    return $this->pageURL($this->prevPage());
  }

  function hasPrevPage() {
    return ($this->page <= 1) ? false : true;
  }

  function nextPage() {
    return ($this->hasNextPage()) ? $this->page+1 : $this->page;
  }

  function nextPageURL() {
    return $this->pageURL($this->nextPage());
  }

  function hasNextPage() {
    return ($this->page >= $this->pages) ? false : true;
  }

  function numStart() {
    return $this->offset+1;
  }

  function numEnd() {
    return $this->offset+$this->limit;
  }

  function range($range=5) {

    if($this->countPages() <= $range) {
      $this->rangeStart = 1;
      $this->rangeEnd   = $this->countPages();
      return range($this->rangeStart, $this->rangeEnd);
    }
    
    $this->rangeStart = $this->page - floor($range/2);  
    $this->rangeEnd   = $this->page + floor($range/2);  
  
    if($this->rangeStart <= 0) {  
      $this->rangeEnd += abs($this->rangeStart)+1;  
      $this->rangeStart = 1;  
    }  

    if($this->rangeEnd > $this->countPages()) {  
      $this->rangeStart -= $this->rangeEnd-$this->countPages();  
      $this->rangeEnd = $this->countPages();  
    }  

    return range($this->rangeStart,$this->rangeEnd);  

  }

  function rangeStart() {
    return $this->rangeStart;  
  }
  
  function rangeEnd() {
    return $this->rangeEnd;
  }

}

