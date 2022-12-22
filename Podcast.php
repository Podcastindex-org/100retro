<?php
include_once "Item.php";
include_once "LiveItem.php";

class Podcast {
    protected $built_once = FALSE;
    public $changed = FALSE;
    public $itunes_ns = "http://www.itunes.com/dtds/podcast-1.0.dtd";
    public $podcast_ns = "https://podcastindex.org/namespace/1.0";
    public $title = "";
    public $description = "";
    public $link = "";
    public $categories = array();
    public $copyright = "";
    public $docs = "http://blogs.law.harvard.edu/tech/rss";
    public $language = "en";
    public $lastBuildDate = "";
    public $managingEditor = "";
    public $pubDate = "";
    public $webMaster = "";
    public $generator = "FreePod";
    public $podcast_locked = array(
        "locked" => "yes",
        "owner" => ""
    );
    public $podcast_funding_url = "";
    public $podcast_funding_text = "";
    public $podcast_guid = "";
    public $podcast_person = array(
        "name" => "",
        "role" => "",
        "group" => "",
        "img" => "",
        "href" => "",
    );
    public $podcast_persons = array();
    public $podcast_location = "";
    public $podcast_medium = "";
    public $podcast_social_interact = array(
        "protocol" => "activitypub",
        "uri" => "",
        "accountId" => "",
        "accountUrl" => "",
    );
    public $itunes_subtitle = "";
    public $itunes_summary = "";
    public $itunes_categories = array();
    public $itunes_keywords = array();
    public $itunes_author = "";
    public $itunes_owner = array(
        "email" => "",
        "name" => ""
    );
    public $itunes_image = "";
    public $itunes_explicit = "no";
    public $image = array(
        "url" => "",
        "title" => "",
        "link" => "",
        "description" => "",
        "width" => 0,
        "height" => 0
    );
    protected $xmlFeed = NULL;
    protected $items = array();
    protected $liveItems = array();
    protected $channel = NULL;
    protected $hash = NULL;
    public $value = array(
        "type" => "lightning",
        "method" => "keysend",
        "suggested" => "0.00000005000"
    );
    public $valueRecipients = array();


    public function __construct( $title = "", $description = "", $link = "" ) {
        if(empty($title)) return FALSE;
        if(empty($description)) return FALSE;
        if(empty($link)) return FALSE;

        //Create the xml feed
        $this->xmlFeed = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
                  <rss xmlns:itunes="'.$this->itunes_ns.'" xmlns:podcast="'.$this->podcast_ns.'" version="2.0"></rss>'
        );
        //Channel
        $this->xmlFeed->addChild("channel");
        $this->channel = $this->xmlFeed->channel;
        //Required
        $this->title = $title;
        $this->description = $description;
        $this->link = $link;
        //Dates
        $this->lastBuildDate = $this->pubDate();
        $this->pubDate = $this->lastBuildDate;

        return(TRUE);
    }

    public function newItem( $title = "", $description = "", $link = "", $guid = "" ) {
        $this->changed = TRUE;
        $item = new Item($title, $description, $link, $guid);
        //Set some defaults
        $item->author = $this->managingEditor;
        $item->itunes_author = $this->itunes_author;
        $item->itunes_explicit = $this->itunes_explicit;
        $item->pubDate = $this->pubDate();
        $this->items[] = $item;

        return($item);
    }

    public function newLiveItem( $title = "", $description = "", $link = "", $guid = "", $status = "pending",
    $start = "", $end = "", $chat = "") {
        $this->changed = TRUE;
        $item = new LiveItem($title, $description, $link, $guid, $status, $start, $end, $chat);
        //Set some defaults
        $item->author = $this->managingEditor;
        $item->itunes_author = $this->itunes_author;
        $item->itunes_explicit = $this->itunes_explicit;
        $item->pubDate = $this->pubDate();
        $this->liveItems[] = $item;

        return($item);
    }

    private function addItem( Item $item ) {
        //Convert channel to a DOM element and import item into it
        $domchannel = dom_import_simplexml($this->xmlFeed->channel);
        $domnew = $domchannel->ownerDocument->importNode($item->domObject(), TRUE);
        $domchannel->appendChild($domnew);
    }

    private function addLiveItem( LiveItem $item ) {
        //Convert channel to a DOM element and import item into it
        $domchannel = dom_import_simplexml($this->xmlFeed->channel);
        $domnew = $domchannel->ownerDocument->importNode($item->domObject(), TRUE);
        $domchannel->appendChild($domnew);
    }

    protected function removeNodes( $val, $ns = "" ) {
        //echo "removeNodes(".$val.",".$ns.")\n";
        if(!empty($ns)) {
            $this->xmlFeed->registerXPathNamespace("default", $ns);
            $nsp = "default:";
        } else {
            $nsp = "";
        }

        if(empty($val)) {
            $val = "*";
        }

        $nsnodes = $this->xmlFeed->xpath('//'.$nsp.$val);

        foreach ( $nsnodes as $child )
        {
            if(isset($child)) {
                //echo print_r($child, TRUE);
                unset($child[0]);
            }
        }
        return(TRUE);
    }

    public function setValue( $key, $val) {
        $this->changed = TRUE;
        $this->$key = $val;

        return(TRUE);
    }

    protected function pubDate( $val = "" ) {
        if(empty($val)) {
            $pd = date(DATE_RSS);
        } else {
            $pd = strtotime($val);
        }
        return($pd);
    }

    public function addCategory( $val, $itunes = FALSE ) {
        $this->changed = TRUE;
        if($itunes) {
            $this->itunes_categories[] = $val;
        } else {
            $this->categories[] = $val;
        }
        return(TRUE);
    }

    public function addCopyright( $val ) {
        $this->changed = TRUE;
        $this->xmlFeed->channel->copyright = $val;
        $this->copyright = $val;
        return(TRUE);
    }

    public function xml( $pretty = FALSE) {
        $this->buildFeedObject();

        //Output the xml
        if($pretty) {
            $dom = new DOMDocument("1.0");
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            $dom->loadXML($this->xmlFeed->asXML());
            return str_replace(' xmlns=""', '', $dom->saveXML());
        } else {
            return $this->xmlFeed->asXML();
        }
    }

    public function xmlObject() {
        return $this->xmlFeed;
    }

    public function domObject() {
        $this->buildFeedObject();
        return dom_import_simplexml($this->xmlFeed);
    }

    public function purgeFeed() {
        //Remove all of the itunes stuff
        $this->removeNodes("", $this->itunes_ns);
        //Remove the category node
        $this->removeNodes("category");
        //Remove the items
        $this->removeNodes("item");
        //Remove pubDate
        $this->removeNodes("pubDate");
        $this->removeNodes("lastBuildDate");
    }

    protected function buildFeedObject() {
        //Clean the feed before rebuilding
        if($this->built_once) $this->purgeFeed();

        //Update pubdate
        $this->pubDate = $this->pubDate();

        //Add the required channel elements
        $this->xmlFeed->channel->title = $this->title;
        $this->xmlFeed->channel->description = $this->description;
        $this->xmlFeed->channel->link = $this->link;
        //Add categories
//        $ctg = "";
//        foreach( $this->categories as $cat ) {
//            $category_node = $this->xmlFeed->channel->addChild("category", "", $this->itunes_ns);
//            $category_node->addAttribute("text", trim($cat));
//        }
        //Copyright
        if(!empty($this->copyright)) {
            $this->xmlFeed->channel->copyright = $this->copyright;
        }
        //Spec stuff
        $this->xmlFeed->channel->docs = $this->docs;
        $this->xmlFeed->channel->language = $this->language;

        //Dates
        $this->xmlFeed->channel->pubDate = $this->pubDate;
        if($this->changed) {
            $this->xmlFeed->channel->lastBuildDate = $this->pubDate;
        } else {
            $this->xmlFeed->channel->lastBuildDate = $this->lastBuildDate;
        }

        //Locked
        if(!empty($this->itunes_owner['email'])) {
            $lockTag = $this->xmlFeed->channel->addChild('locked', "yes", $this->podcast_ns);
            $lockTag->addAttribute("owner", $this->itunes_owner['email']);
        }

        //Funding
        if(!empty($this->podcast_funding_url) && isset($this->podcast_funding_text)) {
            $fundingTag = $this->xmlFeed->channel->addChild(
                'funding',
                (string)$this->podcast_funding_text,
                $this->podcast_ns
            );
            $fundingTag->addAttribute("url", $this->podcast_funding_url);
        }

        //Names
        if(!empty($this->managingEditor)) {
            $this->xmlFeed->channel->managingEditor = $this->managingEditor;
        }
        if(!empty($this->managingEditor) || !empty($this->webMaster)) {
            if(empty($this->webMaster)) {
                $this->webMaster = $this->managingEditor;
            }
            $this->xmlFeed->channel->webMaster = $this->webMaster;
        }
        //System
        $this->xmlFeed->channel->generator = $this->generator;
        if(!empty($this->itunes_owner['email']) || !empty($this->managingEditor)) {
            $this->xmlFeed->channel->addChild('owner', "", $this->itunes_ns);
            if(empty($this->itunes_owner['email'])) {
                $this->itunes_owner['email'] = $this->managingEditor;
            }
            $this->xmlFeed->channel->children(
                'itunes',
                TRUE
            )->owner->email = $this->itunes_owner['email'];
        }
        if(!empty($this->itunes_owner['name'])) {
            $this->xmlFeed->channel->children(
                'itunes',
                TRUE
            )->owner->name = $this->itunes_owner['name'];
        }
        //Album art
        if(!empty($this->itunes_image) || !empty($this->image['url'])) {
            if(empty($this->image['url'])) {
                $this->image['url'] = $this->itunes_image;
            }
            $this->xmlFeed->channel->image->url = $this->image['url'];
            if(empty($this->image['title'])) {
                $this->image['title'] = $this->title;
            }
            $this->xmlFeed->channel->image->title = $this->image['title'];
            if(empty($this->image['link'])) {
                $this->image['link'] = $this->link;
            }
            $this->xmlFeed->channel->image->link = $this->image['link'];
            if(empty($this->image['description'])) {
                $this->image['description'] = $this->description;
            }
            $this->xmlFeed->channel->image->description = $this->image['description'];
            if((empty($this->image['width']) || empty($this->image['height'])) && !empty($this->image['url']) ) {
                list($width, $height, $type, $attr) = getimagesize($this->image['url']);
                $this->image['width'] = $width;
                $this->image['height'] = $height;
            }
            if($this->image['width'] > 144) {
                $this->image['width'] = 144;
            }
            if($this->image['height'] > 144) {
                $this->image['height'] = 144;
            }
            if(!empty($this->image['width']) || !empty($this->image['height'])) {
                $this->xmlFeed->channel->image->width = $this->image['width'];
                $this->xmlFeed->channel->image->height = $this->image['height'];
            }
        }

        //Itunes stuff
        if(!empty($this->itunes_subtitle)) {
            $this->xmlFeed->channel->addChild('subtitle', "", $this->itunes_ns);
            $this->xmlFeed->channel->children('itunes', TRUE)->subtitle = $this->itunes_subtitle;
        }

        $this->xmlFeed->channel->addChild('summary', "", $this->itunes_ns);
        if(empty($this->itunes_summary)) {
            $this->itunes_summary = $this->description;
        }
        $this->xmlFeed->channel->children('itunes', TRUE)->summary = $this->itunes_summary;

        if(!empty($this->itunes_author) || !empty($this->managingEditor)) {
            $this->xmlFeed->channel->addChild('author', "", $this->itunes_ns);
            if (empty($this->itunes_author)) {
                $this->itunes_author = $this->managingEditor;
            }
            $this->xmlFeed->channel->children('itunes', TRUE)->author = $this->itunes_author;
        }

        if(!empty($this->itunes_image) || !empty($this->image['url'])) {
            $this->xmlFeed->channel->addChild('image', "", $this->itunes_ns);
            if(empty($this->itunes_image)) {
                $this->itunes_image = $this->image['url'];
            }
            $this->xmlFeed->channel->children('itunes', TRUE)->image['href'] = $this->itunes_image;
        }

        $this->xmlFeed->channel->addChild('explicit', "", $this->itunes_ns);
        $this->xmlFeed->channel->children('itunes', TRUE)->explicit = $this->itunes_explicit;

        //Itunes keywords
        if(!empty($this->itunes_keywords)) {
            $itksize = 0;
            $itk = "";
            foreach($this->itunes_keywords as $kw) {
                $itksize += strlen($kw.",");
                if($itksize > 255) {
                    break;
                }
                $itk = $itk . "," . $kw;
            }
            $this->xmlFeed->channel->addChild('keywords', "", $this->itunes_ns);
            $this->xmlFeed->channel->children('itunes', TRUE)->keywords = trim(
                $itk,
                " ,"
            );
        }

        //Itunes categories
        if(empty($this->itunes_categories)) {
            //Split category value into an array and add as itunes_category values
            $this->itunes_categories = $this->categories;
        }
        $count = 0;
        foreach($this->itunes_categories as $cat) {
            $this->xmlFeed->channel->addChild('category', "", $this->itunes_ns);
            $this->xmlFeed->channel->children(
                'itunes',
                TRUE
            )->category[$count]->addAttribute("text", $cat);
            $count++;
        }

        //Locations
        if(!empty($this->podcast_location)) {
            $this->xmlFeed->channel->addChild('location', $this->podcast_location, $this->podcast_ns);
        }

        //Persons
        if(!empty($this->podcast_person['name'])) {
            $person = $this->xmlFeed->channel->addChild(
                'person',
                $this->podcast_person['name'],
                $this->podcast_ns
            );
            if(!empty($this->podcast_person['img'])) {
                $person->addAttribute('img', $this->podcast_person['img']);
            }
            if(!empty($this->podcast_person['href'])) {
                $person->addAttribute('href', $this->podcast_person['href']);
            }
        }

        //Guid
        if(!empty($this->podcast_guid)) {
            $this->xmlFeed->channel->addChild('guid', $this->podcast_guid, $this->podcast_ns);
        }

        //Medium
        if(!empty($this->podcast_medium)) {
            $this->xmlFeed->channel->addChild('medium', $this->podcast_medium, $this->podcast_ns);
        }

        //Value
        if(count($this->valueRecipients) > 0 ) {
            $valueTag = $this->xmlFeed->channel->addChild('value', NULL, $this->podcast_ns);
            $valueTag->addAttribute('type', 'lightning');
            $valueTag->addAttribute('method', 'keysend');
            $valueTag->addAttribute('suggested', '0.00000005000');
            $count = 0;
            foreach($this->valueRecipients as $recipient) {
                $valRec = $valueTag->addChild('valueRecipient', NULL, $this->podcast_ns);
                $valRec->addAttribute('name', $recipient['name']);
                $valRec->addAttribute('type', $recipient['type']);
                $valRec->addAttribute('address', $recipient['address']);
                if(isset($recipient['customKey']) && !empty($recipient['customKey'])) {
                    $valRec->addAttribute('customKey', $recipient['customKey']);
                }
                if(isset($recipient['customValue']) && !empty($recipient['customValue'])) {
                    $valRec->addAttribute('customValue', $recipient['customValue']);
                }
                $valRec->addAttribute('split', $recipient['split']);
                $count++;
            }
        }

        //Add all of the live items
        foreach($this->liveItems as $item) {
            $liveItem = $this->addLiveItem($item);
            $liveItem['start'] = $item->start;
            $this->lastBuildDate = $item->pubDate;
        }

        //Add all of the items
        foreach($this->items as $item) {
            $this->addItem($item);
            $this->lastBuildDate = $item->pubDate;
        }

        //We built the feed
        $this->built_once = TRUE;

        //Reset change track
        $this->changed = FALSE;



        return(TRUE);
    }
}