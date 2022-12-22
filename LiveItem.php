<?php
/**
 * Created by PhpStorm.
 * User: DJ
 * Date: 4/5/2015
 * Time: 9:31 PM
 */

class LiveItem extends Podcast {
    public $author = "";
    public $enclosures = array();
    public $guid = array(
        "value" => "",
        "isPermaLink" => FALSE
    );
    public $itunes_duration = "";
    public $podcast_chapters_url = "";
    public $podcast_transcript_url = "";
    public $status = "pending";
    public $chat = "";
    public $start = "";
    public $end = "";


    public function __construct(
        $title = "",
        $description = "",
        $link = "",
        $guid = "",
        $status = "pending",
        $start = "",
        $end = "",
        $chat = ""
    ) {
        //Check default params
        if(empty($title) && empty($description)) return FALSE;

        //Create the xml
        $liveItem = $this->xmlFeed = new SimpleXMLElement(
            '<podcast:liveItem xmlns:itunes="'.$this->itunes_ns.'" xmlns:podcast="'.$this->podcast_ns.'" >
            </podcast:liveItem>'
        );

        $this->status = $status;
        $this->start = $start;
        $this->end = $end;
        $this->chat = $chat;

        $this->title = $title;
        $this->description = $description;
        $this->link = $link;

        $liveItem->addAttribute('status', $this->status);
        $liveItem->addAttribute('start', $this->start);
        $liveItem->addAttribute('end', $this->end);
        if(!empty($this->chat)) {
            $liveItem->addAttribute('chat', $this->chat);
        }

        //Check the guid
        if(!empty($guid) || !empty($link)) {
            if (empty($guid)) {
                $this->guid['value'] = $link;
            } else {
                $this->guid['value'] = $guid;
            }
            if (stripos($this->guid['value'], 'http') === 0) {
                $this->guid['isPermaLink'] = TRUE;
            }
        }

        return(TRUE);
    }

    public function addEnclosure( $url = "", $length = "", $type = "audio/mpeg", $timeout = 5 ) {
        if(empty($url)) return FALSE;

        //Try to get a file size if none given
        if(empty($length)) {
            //Get the content-length header
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            $data = curl_exec($ch);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            //Bail if return is not a 2xx or 3xx
            if($httpcode >= 400) {
                return(FALSE);
            }

            if (preg_match('/Content-Length: (\d+)/', $data, $matches)) {
                // Contains file size in bytes
                $length = (int)$matches[1];
            }
        }

        if(empty($type) && !empty($contentType)) {
            $type = $contentType;
        }

        $this->enclosures[] = array(
            'url'       =>  $url,
            'length'    =>  $length,
            'type'      =>  $type
        );

        return(TRUE);
    }

    public function purgeFeed() {
        //Remove all of the itunes stuff
        $this->removeNodes("", $this->itunes_ns);
    }

    protected function buildFeedObject() {
        //Clean the feed before rebuilding
        if($this->built_once) $this->purgeFeed();

        //Add the required channel elements
        if(!empty($this->title)) {
            $this->xmlFeed->addChild('title', $this->title, '');
        }
        if(!empty($this->description)) {
            $this->xmlFeed->addChild('description', $this->description, '');
        }
        if(!empty($this->link)) {
            $this->xmlFeed->addChild('link', $this->link, '');
        }
        if(!empty($this->guid['value'])) {
            $this->xmlFeed->addChild('guid', $this->guid['value'], '');
            if(!$this->guid['isPermaLink']) {
                $this->xmlFeed->guid['isPermaLink'] = 'false';
            } else {
                $this->xmlFeed->guid['isPermaLink'] = 'true';
            }
        }

        //Dates
        if(!empty($this->pubDate)) {
            $this->xmlFeed->addChild('pubDate', $this->pubDate, '');
        }

        //Podcast stuff
        if(!empty($this->podcast_chapters_url)) {
            $chaptersTag = $this->xmlFeed->addChild('chapters', NULL, $this->podcast_ns);
            $chaptersTag->addAttribute("url", $this->podcast_chapters_url);
            $chaptersTag->addAttribute("type", "application/json");
        }
        if(!empty($this->podcast_transcript_url)) {
            $transcriptTag = $this->xmlFeed->addChild('transcript', NULL, $this->podcast_ns);
            $transcriptTag->addAttribute("url", $this->podcast_transcript_url);
            $transcriptTag->addAttribute("type", "application/srt");
        }

        //Itunes stuff
        if(!empty($this->itunes_subtitle)) {
            $this->xmlFeed->addChild('subtitle', "", $this->itunes_ns);
            $this->xmlFeed->children('itunes', TRUE)->subtitle = $this->itunes_subtitle;
        }

        if(!empty($this->itunes_summary) || !empty($this->description)) {
            $this->xmlFeed->addChild('summary', "", $this->itunes_ns);
            if(empty($this->itunes_summary)) {
                $this->itunes_summary = $this->description;
            }
            $this->xmlFeed->children('itunes', TRUE)->summary = $this->itunes_summary;
        }

        if(!empty($this->itunes_author) || !empty($this->author)) {
            $this->xmlFeed->addChild('author', "", $this->itunes_ns);
            if(empty($this->itunes_author)) $this->itunes_author = $this->author;
            $this->xmlFeed->children('itunes', TRUE)->author = $this->itunes_author;
            if(empty($this->itunes_author)) $this->author = $this->itunes_author;
            $this->xmlFeed->addChild('author', $this->author, '');
        }

        if(!empty($this->itunes_image) || !empty($this->image['url'])) {
            $this->xmlFeed->addChild('image', "", $this->itunes_ns);
            if(empty($this->itunes_image)) $this->itunes_image = $this->image['url'];
            $this->xmlFeed->children('itunes', TRUE)->image['href'] = $this->itunes_image;
        }

        $this->xmlFeed->addChild('explicit', "", $this->itunes_ns);
        $this->xmlFeed->children('itunes', TRUE)->explicit = $this->itunes_explicit;

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
            $this->xmlFeed->addChild('keywords', "", $this->itunes_ns);
            $this->xmlFeed->children('itunes', TRUE)->keywords = trim($itk, " ,");
        }

        //Locations
        if(!empty($this->podcast_location)) {
            $this->xmlFeed->addChild('location', $this->podcast_location, $this->podcast_ns);
        }

        //Persons
        if(!empty($this->podcast_person['name'])) {
            $person = $this->xmlFeed->addChild('person', $this->podcast_person['name'], $this->podcast_ns);
            if(!empty($this->podcast_person['img'])) {
                $person->addAttribute('img', $this->podcast_person['img']);
            }
            if(!empty($this->podcast_person['href'])) {
                $person->addAttribute('href', $this->podcast_person['href']);
            }
        }

        //Social Interact
        if(!empty($this->podcast_social_interact['uri'])) {
            $social = $this->xmlFeed->addChild(
                'socialInteract',
                '',
                $this->podcast_ns
            );
            if(!empty($this->podcast_social_interact['protocol'])) {
                $social->addAttribute('protocol', $this->podcast_social_interact['protocol']);
            }
            $social->addAttribute('uri', $this->podcast_social_interact['uri']);
            if(!empty($this->podcast_social_interact['accountId'])) {
                $social->addAttribute('accountId', $this->podcast_social_interact['accountId']);
            }
            if(!empty($this->podcast_social_interact['accountUrl'])) {
                $social->addAttribute('accountUrl', $this->podcast_social_interact['accountUrl']);
            }
        }

        //Enclosures
        $count = 0;
        foreach($this->enclosures as $enclosure) {
            $this->xmlFeed->addChild('enclosure', '', '');
            $this->xmlFeed->enclosure[$count]['url'] = $enclosure['url'];
            $this->xmlFeed->enclosure[$count]['length'] = $enclosure['length'];
            $this->xmlFeed->enclosure[$count]['type'] = $enclosure['type'];
            $count++;
        }

        //Value
        if(count($this->valueRecipients) > 0 ) {
            $valueTag = $this->xmlFeed->addChild('value', NULL, $this->podcast_ns);
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

        //We built the feed
        $this->built_once = TRUE;

        return(TRUE);
    }
}