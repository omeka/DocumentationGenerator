<?php

namespace Sphpdox;

class CommentParser
{
    protected $comment;
    protected $shortDescription;
    protected $longDescription = null;
    protected $annotations = array();

    /**
     * @param string $docblock
     */
    public function __construct($comment)
    {
        $this->comment = $comment;
        $this->parse();
    }

    protected function parse()
    {
        $content = $this->comment;

        // Rewrite newlines
        $content = preg_replace('/\r\n/', "\n", $content);
        
        // Rewrite tabs
        $content = preg_replace('/\t/', '    ', $content);

        // Remove initial whitespace
        $content = preg_replace('/^\s*/m', '', $content);

        // Remove start and end comment markers
        $content = preg_replace('/\s*\/\*\*#?@?\+?\s*/m', '', $content);
        $content = preg_replace('/\s*\*\/\s*/m', '', $content);

        // Remove start of line comment markers
        $content = preg_replace('/^\* ?/m', '', $content);

        // Split the comment into parts
        $this->split($content);
    }

    
    
    protected function split($content)
    {      
        // Pull off all annotation lines
        $continuation = false;
        $annotation = '';

        $lines = explode("\n", $content);
        $remaining = $lines;

        //PMJ
        $keepFormatting = false;

        foreach ($lines as $i => $line) {
            if (!$line) {
                $continuation = false;
                continue;
            }          
            if($line == '<code>') {
                $keepFormatting = true;
                $line = "\n\n    .. code-block:: php \n\n";
            }
            if($line == "<ul>" || $line == "<ol>") {
                $keepFormatting = true;
                $tag = $line;
                $line = "\n\n    .. raw:: html\n\n       $line";                
            }            
            
            if ($line[0] == '@') {
                if ($annotation) {
                    $this->annotations[] = $annotation;
                }
                $annotation = '';
                $continuation = true;
            } elseif ($continuation) {
                $annotation .= ' ';
            } else {
                continue;
            }
            if($keepFormatting) {
                if($line == '</code>') {
                    $keepFormatting = false;
                } else if ($line == "</ul>" || $line == "</ol>") {
                    $annotation .= "        $line\n";
                    $keepFormatting = false;
                } else {
                    $annotation .= "        $line\n";
                }
                
            } else {
                $annotation .= trim($line);
            }

            unset($remaining[$i]);
        }

        if ($annotation) {
            $this->annotations[] = $annotation;     
        }

        // Split remaining lines by paragrah
        $remaining = implode("\n", $remaining);
        $parts = preg_split("/(\n\n|\r\n\r\n)/", $remaining, -1, PREG_SPLIT_NO_EMPTY);
        //$parts = preg_split("/(\n\n|\r\n\r\n)/", $remaining);

        // Into two parts
        if ($parts) {
            $first = $parts[0];
            $this->shortDescription = trim($first);

            $rest = array_slice($parts, 1);

            foreach($rest as $i=>$line) {
                $lines = explode("\n", $line);
                
                if(count($lines) > 2) {
                    
                    $rest[$i] = $this->linesToText($lines);
                }
            }

            if ($rest) {
                $long = implode("\n\n", $rest);
                $long = preg_replace('/(\w) *\n *(\w)/', '\1 \2', $long);
                $long = trim($long);
                $this->longDescription = $long;
            }
        }
    }

    
    /**
     * PMJ added to work the code and html seeking stuff into general use
     * duplicates, sadly, much of above
     */
    protected function linesToText($lines)
    {
        $text = '';
        $keepFormatting = false;
        foreach ($lines as $line) {
            if($line == '<code>') {
                $keepFormatting = true;
                $line = "\n\n    .. code-block:: php \n\n";
            }
            if($line == "<ul>" || $line == "<ol>") {
                $keepFormatting = true;
                $tag = $line;
                $line = "\n\n    .. raw:: html\n\n       $line";        
            }
                   
            if($keepFormatting) {
                if($line == '</code>') {
                    $keepFormatting = false;
                } else if ($line == "</ul>" || $line == "</ol>") {
                    $text .= "\t                $line\n";
                    $keepFormatting = false;
                } else {
                    $text .= "\t               $line\n";
                }       
            } else if(substr($line, 0, 1) == '-') {
                $line = "\n\n$line";
                $text .= $line;
            } else {
                $text .= trim($line);
            }        

        }
        return $text;        
    }

    public function getAnnotations()
    {
        return $this->annotations;
    }

    public function getDescription()
    {
        $description = $this->getShortDescription();
        if ($this->hasLongDescription()) {
            $description .= "\n\n" . $this->getLongDescription();            
        }        
        return $description;
    }

    public function getShortDescription()
    {
        return $this->shortDescription;
    }

    public function getLongDescription()
    {
        return $this->longDescription;
         
    }

    public function hasDescription()
    {
        return (boolean)$this->shortDescription;
    }

    public function hasLongDescription()
    {
        return $this->longDescription != null;
    }
}