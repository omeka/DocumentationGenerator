<?php

namespace Sphpdox\Element;

use Symfony\Component\Console\Output\OutputInterface;
use TokenReflection\ReflectionConstant;

/**
 * Constant element
 */
class ConstantElement extends Element
{
    //PMJ commented out the ReflectionConstant class name b/c sometimes PHP/ReflectionConstant got passed in
    public function __construct($constant)
    {
        $this->reflection = $constant;
    }

    /**
     * @see Sphpdox\Element.Element::__toString()
     */
    public function __toString()
    {
        $string = '';

        if ($this->reflection->getDocComment()) {
            $string = sprintf(".. php:const:: %s\n\n", $this->reflection->getName());

            $parser = $this->getParser();
            if ($parser->hasDescription()) {
                $description = $parser->getDescription();
                if ($description) {
                    $string .= "\n\n";
                    $string .= $this->indent($description, 4);
                }
            }
        }

        return $string;
    }
}