<?php

namespace Sphpdox\Element;

use TokenReflection\IReflectionConstant;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Constant element
 */
class ConstantElement extends Element
{
    public function __construct(IReflectionConstant $constant)
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