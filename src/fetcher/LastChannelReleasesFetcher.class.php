<?php
lmb_require('limb/datasource/src/lmbArrayDataset.class.php');
lmb_require('limb/web_app/src/fetcher/lmbFetcher.class.php');

@define('LAST_CHANNEL_RELEASES_CACHE', LIMB_VAR_DIR . '/channel_items.cache');
@define('LAST_CHANNEL_RELEASES_CACHE_TIME', 5*60*60);
//@define('LAST_CHANNEL_RELEASES_CACHE_TIME', 0);
@define('LAST_CHANNEL_RELEASES_RSS_URL', 'http://pear.limb-project.com/index.php?rss&latest');
@define('LAST_CHANNEL_RELEASES_ITEMS', 5);

class LastChannelReleasesFetcher extends lmbFetcher
{
  protected function _createDataSet()
  {
    return new lmbArrayDataset($this->_retrieveItems());
  }

  protected function _retrieveItems()
  {
    $items = array();

    if($items = $this->_readCache())
      return $items;

    $xml = get_url_contents(LAST_CHANNEL_RELEASES_RSS_URL);
    if($rss = @simplexml_load_string($xml))
    {
      foreach($rss->item as $item)
      {
        $ns_dc = $item->children('http://purl.org/dc/elements/1.1/');
        $items[] = array('date' => (string)$ns_dc->date,
                           'link' => (string)$item->link,
                           'title' => (string)$item->title);

        if(sizeof($items) + 1 > LAST_CHANNEL_RELEASES_ITEMS)
          break;
      }
      $this->_writeCache($items);
    }
    else  //fallback
      $items = $this->_readCache(false);

    return $items;
  }

  protected function _readCache($check_time = true)
  {
    if(file_exists(LAST_CHANNEL_RELEASES_CACHE))
    {
      if(!$check_time || ((time() - filemtime(LAST_CHANNEL_RELEASES_CACHE)) < LAST_CHANNEL_RELEASES_CACHE_TIME))
        return unserialize(file_get_contents(LAST_CHANNEL_RELEASES_CACHE));
    }
    return array();
  }

  protected function _writeCache($cache)
  {
    file_put_contents(LAST_CHANNEL_RELEASES_CACHE, serialize($cache), LOCK_EX);
  }
}

?>