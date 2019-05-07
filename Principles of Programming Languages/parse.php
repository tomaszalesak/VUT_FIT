#!/usr/bin/env php7.3
<?php

/**
 * Class ParseArguments
 */
class ParseArguments
{
    static $help = FALSE;
    static $stats = FALSE;
    static $loc = FALSE;
    static $comments = FALSE;
    static $labels = FALSE;
    static $jumps = FALSE;
    static $dir;
    static $order = array();

    /**
     * sets arguments to static variables or raises ParseException
     * @param array $arguments
     * @param int $length
     * @throws ParseException
     */
    public static function getArguments(array $arguments, int $length)
    {
        unset($arguments[0]);

        foreach ($arguments as $key => $value) {

            if (preg_match("/^--stats=.*$/", $value)) {
                if (self::$stats) {
                    throw new ParseException("a lot of --stats", 10);
                } else {
                    self::$stats = TRUE;
                    self::$dir = substr($value, 8);
                }
            } elseif ($value === "--help") {
                if ($length !== 2) {
                    throw new ParseException("--help must be alone", 10);
                }
                if (self::$help) {
                    throw new ParseException("Multiple occurrence of arg --help", 10);
                } else {
                    self::$help = TRUE;
                }
            } elseif ($value === "--loc") {
                if (self::$loc) {
                    throw new ParseException("Multiple occurrence of arg --loc", 10);
                } else {
                    self::$loc = TRUE;
                    array_push(self::$order, "loc");
                }
            } elseif ($value === "--comments") {
                if (self::$comments) {
                    throw new ParseException("Multiple occurrence of arg --comments", 10);
                } else {
                    self::$comments = TRUE;
                    array_push(self::$order, "comments");
                }
            } elseif ($value === "--labels") {
                if (self::$labels) {
                    throw new ParseException("Multiple occurrence of arg --labels", 10);
                } else {
                    self::$labels = TRUE;
                    array_push(self::$order, "labels");
                }
            } elseif ($value === "--jumps") {
                if (self::$jumps) {
                    throw new ParseException("Multiple occurrence of arg --jumps", 10);
                } else {
                    self::$jumps = TRUE;
                    array_push(self::$order, "jumps");
                }
            } else {
                throw new ParseException("Wrong arg", 10);
            }
        }

        if ((self::$loc || self::$comments || self::$labels || self::$jumps) && !self::$stats) {
            throw new ParseException("argument --stats missing", 10);
        }
    }

    /**
     * writes values of arguments (static variables) to stderr
     */
    public static function ArgsToSTDERR()
    {
        fwrite(STDERR, "--help: " . self::$help . "\n");
        fwrite(STDERR, "--stats: " . self::$stats . "\n");
        fwrite(STDERR, "--loc: " . self::$loc . "\n");
        fwrite(STDERR, "--comments: " . self::$comments . "\n");
        fwrite(STDERR, "--labels: " . self::$labels . "\n");
        fwrite(STDERR, "--jumps: " . self::$jumps . "\n");
        fwrite(STDERR, "dir: " . self::$dir . "\n");
    }
}

/**
 * Class ParseException
 */
class ParseException extends Exception
{
    public $message;
    public $code = 0;

    /**
     * ParseException constructor.
     * @param string $message - to write to stderr
     * @param int $code error - code to exit script
     */
    public function __construct(string $message = "", int $code = 0)
    {
        $this->message = $message;
        $this->code = $code;
        fwrite(STDERR, $message . "\n");
    }
}

/**
 * write help to stdout
 */
function help()
{
    print "Použití: php7.3 parse.php [--help] [--stats=<file> [--loc] [--comments] [--labels] [--jumps]]\n";
    print "     --help            tahle pomoc\n";
    print "     --stats=<file>    vypíše statistiky do souboru <file>\n";
    print "     --loc             vypíše do statistik počet řádků s instrukcemi\n";
    print "     --comments        vypíše do statistik počet řádků, na kterých se vyskytoval komentář\n";
    print "     --labels          vypíše do statistik počet definovaných návěští\n";
    print "     --jumps           vypíše do statistik počet instrukcí pro podmíněné a nepodmíněné skoky dohromady\n";
}

/**
 * returns new line from stdin if exists
 * @return Generator
 * @throws ParseException
 */
function getLine()
{
    $stdin = fopen("php://stdin", "r") or die(11);
    $firstline = strtolower(fgets($stdin));
    if (trim(uncomment($firstline)) != ".ippcode19") {
        throw new ParseException("missing .IPPcode19", 21);
    }
    while ($line = fgets($stdin)) {
        $line = trim(uncomment($line));
        $line = preg_replace('![\s\t]+!', ' ', $line);
        if (strlen($line) < 1) {
            continue;
        }
        yield $line;
    }
}

/**
 * remove comments of ippcode2019
 * @param $comment
 * @return bool|string
 */
function uncomment($comment)
{
    $position = strpos($comment, "#");
    if ($position === FALSE) {
        return $comment;
    } else {
        $GLOBALS['comments']++;
        return substr($comment, 0, $position);
    }
}

/**
 * returns whether arg $bool is a bool value in ippcode2019
 * @param $bool
 * @return false|int
 */
function isBool($bool)
{
    return preg_match("/^bool@((false)|(true))$/", $bool); //ok
}

/**
 * returns whether arg $int is a int value in ippcode2019
 * @param $int
 * @return false|int
 */
function isInt($int)
{
    return preg_match("/^int@[\x2D\x2B]?[0-9]+$/", $int); //ok
}

/**
 * returns whether arg $str is a string value in ippcode2019
 * @param $str
 * @return false|int
 */
function isStr($str)
{
    return preg_match("/^string@([A-Za-z\x{0021}\x{0022}\x{0024}-\x{005B}\x{005D}-\x{FFFF}|(\\\\[0-90-90-9])*$/u", $str); //ok
}

/**
 * returns whether arg $nil is a nil value in ippcode2019
 * @param $nil
 * @return false|int
 */
function isNil($nil)
{
    return preg_match("/^nil@nil$/", $nil); //ok
}

/**
 * returns whether arg $type is a type value in ippcode2019
 * @param $type
 * @return false|int
 */
function isType($type)
{
    return preg_match("/^int|string|bool$/", $type);
}

/**
 * returns whether arg $var is a variable in ippcode2019
 * @param $var
 * @return false|int
 */
function isVar($var)
{
    return preg_match("/^((LF)|(TF)|(GF))@([a-zA-Z]|-|[_$&%*!?])([a-zA-Z]|-|[_$&%*!?]|[0-9]+)*$/", $var); //ok
}

/**
 * returns whether arg $const is a constant in ippcode2019
 * @param $const
 * @return bool
 */
function isConst($const)
{
    return isBool($const) || isStr($const) || isInt($const) || isNil($const); //ok
}

/**
 * returns whether arg $symbol is a symbol in ippcode2019
 * @param $symbol
 * @return bool
 */
function isSymbol($symbol)
{
    return isConst($symbol) || isVar($symbol); //ok
}

/**
 * returns whether arg $label is a label in ippcode2019
 * @param $label
 * @return false|int
 */
function isLabel($label)
{
    return preg_match("/^([a-zA-Z]|-|[_$&%*!?])([a-zA-Z]|-|[_$&%*!?]|[0-9]+)*$/", $label); //ok
}

/**
 * @param $opcode
 * @param $dom
 * @param $programNode
 * @param $instruction_no
 * @param $instruction_array
 * @throws ParseException
 */
function xmlNoArg($opcode, $dom, $programNode, $instruction_no, $instruction_array)
{
    if (count($instruction_array) !== 1) {
        throw new ParseException("noarg: wrong number of args", 23);
    }
    $instructionNode = $dom->createElement("instruction");
    $instructionNode->setAttribute("order", $instruction_no);
    $instructionNode->setAttribute("opcode", $opcode);
    $programNode->appendChild($instructionNode);
}

/**
 * @param $opcode
 * @param $dom
 * @param $programNode
 * @param $instruction_no
 * @param $instruction_array
 * @throws ParseException
 */
function xmlLabel($opcode, $dom, $programNode, $instruction_no, $instruction_array)
{
    if (count($instruction_array) !== 2) {
        throw new ParseException("call: wrong number of args", 23);
    }
    if (isLabel($instruction_array[1])) {
        $GLOBALS['labels'][$instruction_array[1]] = 1;
        $instructionNode = $dom->createElement("instruction");
        $instructionNode->setAttribute("order", $instruction_no);
        $instructionNode->setAttribute("opcode", $opcode);
        $arg1Node = $dom->createElement("arg1");
        $arg1Node->setAttribute("type", "label");
        $arg1Node->textContent = $instruction_array[1];
        $instructionNode->appendChild($arg1Node);
        $programNode->appendChild($instructionNode);
    } else {
        throw new ParseException("DEFVAR: wrong args", 23);
    }
}

/**
 * @param $opcode
 * @param $dom
 * @param $programNode
 * @param $instruction_no
 * @param $instruction_array
 * @throws ParseException
 */
function xmlVar($opcode, $dom, $programNode, $instruction_no, $instruction_array)
{
    if (count($instruction_array) !== 2) {
        throw new ParseException("vari: wrong number of args", 23);
    }
    if (isVar($instruction_array[1])) {
        $instructionNode = $dom->createElement("instruction");
        $instructionNode->setAttribute("order", $instruction_no);
        $instructionNode->setAttribute("opcode", $opcode);
        $arg1Node = $dom->createElement("arg1");
        $arg1Node->setAttribute("type", "var");
        $arg1Node->textContent = $instruction_array[1];
        $instructionNode->appendChild($arg1Node);
        $programNode->appendChild($instructionNode);
    } else {
        throw new ParseException("vari: wrong args", 23);
    }
}

/**
 * @param $opcode
 * @param $dom
 * @param $programNode
 * @param $instruction_no
 * @param $instruction_array
 * @throws ParseException
 */
function xmlVarType($opcode, $dom, $programNode, $instruction_no, $instruction_array)
{
    if (count($instruction_array) !== 3) {
        throw new ParseException("vartype: wrong number of args", 23);
    }
    if (isVar($instruction_array[1]) && isType($instruction_array[2])) {
        $instructionNode = $dom->createElement("instruction");
        $instructionNode->setAttribute("order", $instruction_no);
        $instructionNode->setAttribute("opcode", $opcode);
        $arg1Node = $dom->createElement("arg1");
        $arg1Node->setAttribute("type", "var");
        $arg1Node->textContent = $instruction_array[1];
        $arg2Node = $dom->createElement("arg2");
        $arg2Node->setAttribute("type", "type");
        $arg2Node->textContent = $instruction_array[2];
        $instructionNode->appendChild($arg1Node);
        $instructionNode->appendChild($arg2Node);
        $programNode->appendChild($instructionNode);
    } else {
        throw new ParseException("vari: wrong args", 23);
    }
}

/**
 * @param $opcode
 * @param $dom
 * @param $programNode
 * @param $instruction_no
 * @param $instruction_array
 * @throws ParseException
 */
function xmlSymb($opcode, $dom, $programNode, $instruction_no, $instruction_array)
{
    if (count($instruction_array) !== 2) {
        throw new ParseException("symb: wrong number of args", 23);
    }

    if (isSymbol($instruction_array[1])) {
        $instructionNode = $dom->createElement("instruction");
        $instructionNode->setAttribute("order", $instruction_no);
        $instructionNode->setAttribute("opcode", $opcode);
        $arg1Node = $dom->createElement("arg1");
        if (isConst($instruction_array[1])) {
            if (isInt($instruction_array[1])) {
                $arg1Node->setAttribute("type", "int");
                $int = explode('@', $instruction_array[1]);
                $arg1Node->textContent = $int[1];
            } elseif (isBool($instruction_array[1])) {
                $arg1Node->setAttribute("type", "bool");
                $bool = explode('@', $instruction_array[1]);
                $arg1Node->textContent = $bool[1];
            } elseif (isStr($instruction_array[1])) {
                $arg1Node->setAttribute("type", "string");
                $string = explode('@', $instruction_array[1]);
                $arg1Node->textContent = $string[1];
            } elseif (isNil($instruction_array[1])) {
                $arg1Node->setAttribute("type", "nil");
                $arg1Node->textContent = "nil";
            } else {
                throw new ParseException("symb: wrong type", 23);
            }

        } elseif (isVar($instruction_array[1])) {
            $arg1Node->setAttribute("type", "var");
            $arg1Node->textContent = $instruction_array[1];
        } else {
            throw new ParseException("symb: wrong type 2", 23);
        }

        $instructionNode->appendChild($arg1Node);
        $programNode->appendChild($instructionNode);
    } else {
        throw new ParseException("symb: wrong args", 23);
    }
}

/**
 * @param $opcode
 * @param $dom
 * @param $programNode
 * @param $instruction_no
 * @param $instruction_array
 * @throws ParseException
 */
function xmlVarSymb($opcode, $dom, $programNode, $instruction_no, $instruction_array)
{
    if (count($instruction_array) !== 3) {
        throw new ParseException("varSymb: wrong number of args", 23);
    }

    if (isVar($instruction_array[1]) && isSymbol($instruction_array[2])) {
        $instructionNode = $dom->createElement("instruction");
        $instructionNode->setAttribute("order", $instruction_no);
        $instructionNode->setAttribute("opcode", $opcode);
        $arg1Node = $dom->createElement("arg1");
        $arg1Node->setAttribute("type", "var");
        $arg1Node->textContent = $instruction_array[1];
        $arg2Node = $dom->createElement("arg2");
        if (isConst($instruction_array[2])) {
            if (isInt($instruction_array[2])) {
                $arg2Node->setAttribute("type", "int");
                $int = explode('@', $instruction_array[2]);
                $arg2Node->textContent = $int[1];
            } elseif (isBool($instruction_array[2])) {
                $arg2Node->setAttribute("type", "bool");
                $bool = explode('@', $instruction_array[2]);
                $arg2Node->textContent = $bool[1];
            } elseif (isStr($instruction_array[2])) {
                $arg2Node->setAttribute("type", "string");
                $string = explode('@', $instruction_array[2]);
                $arg2Node->textContent = $string[1];
            } elseif (isNil($instruction_array[2])) {
                $arg2Node->setAttribute("type", "nil");
                $arg2Node->textContent = "nil";
            } else {
                throw new ParseException("varSymb: wrong type", 23);
            }

        } elseif (isVar($instruction_array[2])) {
            $arg2Node->setAttribute("type", "var");
            $arg2Node->textContent = $instruction_array[2];
        } else {
            throw new ParseException("varSymb: wrong type 2", 23);
        }

        $instructionNode->appendChild($arg1Node);
        $instructionNode->appendChild($arg2Node);
        $programNode->appendChild($instructionNode);
    } else {
        throw new ParseException("varSymb: wrong args", 23);
    }
}

/**
 * @param $opcode
 * @param $dom
 * @param $programNode
 * @param $instruction_no
 * @param $instruction_array
 * @throws ParseException
 */
function xmlVarSymb1Symb2($opcode, $dom, $programNode, $instruction_no, $instruction_array)
{
    if (count($instruction_array) !== 4) {
        throw new ParseException("varSymb1Symb2: wrong number of args", 23);
    }

    if (isVar($instruction_array[1]) && isSymbol($instruction_array[2]) && isSymbol($instruction_array[3])) {
        $instructionNode = $dom->createElement("instruction");
        $instructionNode->setAttribute("order", $instruction_no);
        $instructionNode->setAttribute("opcode", $opcode);
        $arg1Node = $dom->createElement("arg1");
        $arg1Node->setAttribute("type", "var");
        $arg1Node->textContent = $instruction_array[1];
        $arg2Node = $dom->createElement("arg2");
        if (isConst($instruction_array[2])) {
            if (isInt($instruction_array[2])) {
                $arg2Node->setAttribute("type", "int");
                $int = explode('@', $instruction_array[2]);
                $arg2Node->textContent = $int[1];
            } elseif (isBool($instruction_array[2])) {
                $arg2Node->setAttribute("type", "bool");
                $bool = explode('@', $instruction_array[2]);
                $arg2Node->textContent = $bool[1];
            } elseif (isStr($instruction_array[2])) {
                $arg2Node->setAttribute("type", "string");
                $string = explode('@', $instruction_array[2]);
                $arg2Node->textContent = $string[1];
            } elseif (isNil($instruction_array[2])) {
                $arg2Node->setAttribute("type", "nil");
                $arg2Node->textContent = "nil";
            } else {
                throw new ParseException("vss: wrong type", 23);
            }

        } elseif (isVar($instruction_array[2])) {
            $arg2Node->setAttribute("type", "var");
            $arg2Node->textContent = $instruction_array[2];
        } else {
            throw new ParseException("varSymb1Symb2: wrong type 2", 23);
        }
        $arg3Node = $dom->createElement("arg3");
        if (isConst($instruction_array[3])) {
            if (isInt($instruction_array[3])) {
                $arg3Node->setAttribute("type", "int");
                $int = explode('@', $instruction_array[3]);
                $arg3Node->textContent = $int[1];
            } elseif (isBool($instruction_array[3])) {
                $arg3Node->setAttribute("type", "bool");
                $bool = explode('@', $instruction_array[3]);
                $arg3Node->textContent = $bool[1];
            } elseif (isStr($instruction_array[3])) {
                $arg3Node->setAttribute("type", "string");
                $string = explode('@', $instruction_array[3]);
                $arg3Node->textContent = $string[1];
            } elseif (isNil($instruction_array[3])) {
                $arg3Node->setAttribute("type", "nil");
                $arg3Node->textContent = "nil";
            } else {
                throw new ParseException("vss: wrong type", 23);
            }

        } elseif (isVar($instruction_array[3])) {
            $arg3Node->setAttribute("type", "var");
            $arg3Node->textContent = $instruction_array[3];
        } else {
            throw new ParseException("varSymb1Symb23: wrong type 2", 23);
        }

        $instructionNode->appendChild($arg1Node);
        $instructionNode->appendChild($arg2Node);
        $instructionNode->appendChild($arg3Node);
        $programNode->appendChild($instructionNode);
    } else {
        throw new ParseException("varSymb1Symb2: wrong args", 23);
    }
}

/**
 * @param $opcode
 * @param $dom
 * @param $programNode
 * @param $instruction_no
 * @param $instruction_array
 * @throws ParseException
 */
function xmlLabelSymb1Symb2($opcode, $dom, $programNode, $instruction_no, $instruction_array)
{
    if (count($instruction_array) !== 4) {
        throw new ParseException("labelSymb1Symb2: wrong number of args", 23);
    }

    if (isLabel($instruction_array[1]) && isSymbol($instruction_array[2]) && isSymbol($instruction_array[3])) {
        $GLOBALS['labels'][$instruction_array[1]] = 1;
        $instructionNode = $dom->createElement("instruction");
        $instructionNode->setAttribute("order", $instruction_no);
        $instructionNode->setAttribute("opcode", $opcode);
        $arg1Node = $dom->createElement("arg1");
        $arg1Node->setAttribute("type", "label");
        $arg1Node->textContent = $instruction_array[1];
        $arg2Node = $dom->createElement("arg2");
        if (isConst($instruction_array[2])) {
            if (isInt($instruction_array[2])) {
                $arg2Node->setAttribute("type", "int");
                $int = explode('@', $instruction_array[2]);
                $arg2Node->textContent = $int[1];
            } elseif (isBool($instruction_array[2])) {
                $arg2Node->setAttribute("type", "bool");
                $bool = explode('@', $instruction_array[2]);
                $arg2Node->textContent = $bool[1];
            } elseif (isStr($instruction_array[2])) {
                $arg2Node->setAttribute("type", "string");
                $string = explode('@', $instruction_array[2]);
                $arg2Node->textContent = $string[1];
            } elseif (isNil($instruction_array[2])) {
                $arg2Node->setAttribute("type", "nil");
                $arg2Node->textContent = "nil";
            } else {
                throw new ParseException("labelsymbsymb: wrong type", 23);
            }

        } elseif (isVar($instruction_array[2])) {
            $arg2Node->setAttribute("type", "var");
            $arg2Node->textContent = $instruction_array[2];
        } else {
            throw new ParseException("labelsymbsymb2: wrong type 2", 23);
        }
        $arg3Node = $dom->createElement("arg3");
        if (isConst($instruction_array[3])) {
            if (isInt($instruction_array[3])) {
                $arg3Node->setAttribute("type", "int");
                $int = explode('@', $instruction_array[3]);
                $arg3Node->textContent = $int[1];
            } elseif (isBool($instruction_array[3])) {
                $arg3Node->setAttribute("type", "bool");
                $bool = explode('@', $instruction_array[3]);
                $arg3Node->textContent = $bool[1];
            } elseif (isStr($instruction_array[3])) {
                $arg3Node->setAttribute("type", "string");
                $string = explode('@', $instruction_array[3]);
                $arg3Node->textContent = $string[1];
            } elseif (isNil($instruction_array[3])) {
                $arg3Node->setAttribute("type", "nil");
                $arg3Node->textContent = "nil";
            } else {
                throw new ParseException("labelsymbsymb: wrong type", 23);
            }

        } elseif (isVar($instruction_array[3])) {
            $arg3Node->setAttribute("type", "var");
            $arg3Node->textContent = $instruction_array[3];
        } else {
            throw new ParseException("labelsymbsymb23: wrong type 2", 23);
        }

        $instructionNode->appendChild($arg1Node);
        $instructionNode->appendChild($arg2Node);
        $instructionNode->appendChild($arg3Node);
        $programNode->appendChild($instructionNode);
    } else {
        throw new ParseException("labelsymbsymb2: wrong args", 23);
    }
}

/**
 * @param $doc
 * @return DOMDocument - xml interpretation of ippcode19 instruction as DOM document
 * @throws ParseException
 */
function Parse($doc)
{
    $dom = new DOMDocument('1.0', 'UTF-8');
    $programNode = $dom->createElement("program");
    $programNode->setAttribute("language", "IPPcode19");
    $instruction_no = 1;

    foreach ($doc as $line) {
        $instruction_array = explode(' ', trim($line));
        $instruction_code = strtoupper($instruction_array[0]);

        switch ($instruction_code) {
            // MOVE var symb
            case 'MOVE':
                xmlVarSymb("MOVE", $dom, $programNode, $instruction_no, $instruction_array);
                $instruction_no++;
                break;
            // CREATEFRAME
            case 'CREATEFRAME':
                xmlNoArg("CREATEFRAME", $dom, $programNode, $instruction_no, $instruction_array);
                $instruction_no++;
                break;
            // PUSHFRAME
            case 'PUSHFRAME':
                xmlNoArg("PUSHFRAME", $dom, $programNode, $instruction_no, $instruction_array);
                $instruction_no++;
                break;
            // POPFRAME
            case 'POPFRAME':
                xmlNoArg("POPFRAME", $dom, $programNode, $instruction_no, $instruction_array);
                $instruction_no++;
                break;
            // DEFVAR <var>
            case 'DEFVAR':
                xmlVar("DEFVAR", $dom, $programNode, $instruction_no, $instruction_array);
                $instruction_no++;
                break;
            // CALL <label>
            case 'CALL':
                xmlLabel("CALL", $dom, $programNode, $instruction_no, $instruction_array);
                $instruction_no++;
                $GLOBALS['jumps']++;
                break;
            // RETURN
            case 'RETURN':
                xmlNoArg("RETURN", $dom, $programNode, $instruction_no, $instruction_array);
                $instruction_no++;
                break;
            // PUSHS <symb>
            case 'PUSHS':
                xmlSymb("PUSHS", $dom, $programNode, $instruction_no, $instruction_array);
                $instruction_no++;
                break;
            // POPS <var>
            case 'POPS':
                xmlVar("POPS", $dom, $programNode, $instruction_no, $instruction_array);
                $instruction_no++;
                break;
            case 'ADD':
                xmlVarSymb1Symb2("ADD", $dom, $programNode, $instruction_no, $instruction_array);
                $instruction_no++;
                break;
            case 'SUB':
                xmlVarSymb1Symb2("SUB", $dom, $programNode, $instruction_no, $instruction_array);
                $instruction_no++;
                break;
            case 'MUL':
                xmlVarSymb1Symb2("MUL", $dom, $programNode, $instruction_no, $instruction_array);
                $instruction_no++;
                break;
            case 'IDIV':
                xmlVarSymb1Symb2("IDIV", $dom, $programNode, $instruction_no, $instruction_array);
                $instruction_no++;
                break;
            case 'LT':
                xmlVarSymb1Symb2("LT", $dom, $programNode, $instruction_no, $instruction_array);
                $instruction_no++;
                break;
            case 'GT':
                xmlVarSymb1Symb2("GT", $dom, $programNode, $instruction_no, $instruction_array);
                $instruction_no++;
                break;
            case 'EQ':
                xmlVarSymb1Symb2("EQ", $dom, $programNode, $instruction_no, $instruction_array);
                $instruction_no++;
                break;
            case 'AND':
                xmlVarSymb1Symb2("AND", $dom, $programNode, $instruction_no, $instruction_array);
                $instruction_no++;
                break;
            case 'OR':
                xmlVarSymb1Symb2("OR", $dom, $programNode, $instruction_no, $instruction_array);
                $instruction_no++;
                break;
            case 'NOT':
                xmlVarSymb1Symb2("NOT", $dom, $programNode, $instruction_no, $instruction_array);
                $instruction_no++;
                break;
            // var symb
            case 'INT2CHAR':
                xmlVarSymb("INT2CHAR", $dom, $programNode, $instruction_no, $instruction_array);
                $instruction_no++;
                break;
            case 'STRI2INT':
                xmlVarSymb1Symb2("STRI2INT", $dom, $programNode, $instruction_no, $instruction_array);
                $instruction_no++;
                break;
            case 'READ':
                xmlVarType("READ", $dom, $programNode, $instruction_no, $instruction_array);
                $instruction_no++;
                break;
            case 'WRITE':
                xmlSymb("WRITE", $dom, $programNode, $instruction_no, $instruction_array);
                $instruction_no++;
                break;
            case 'CONCAT':
                xmlVarSymb1Symb2("CONCAT", $dom, $programNode, $instruction_no, $instruction_array);
                $instruction_no++;
                break;
            case 'STRLEN':
                xmlVarSymb("STRLEN", $dom, $programNode, $instruction_no, $instruction_array);
                $instruction_no++;
                break;
            case 'GETCHAR':
                xmlVarSymb1Symb2("GETCHAR", $dom, $programNode, $instruction_no, $instruction_array);
                $instruction_no++;
                break;
            case 'SETCHAR':
                xmlVarSymb1Symb2("SETCHAR", $dom, $programNode, $instruction_no, $instruction_array);
                $instruction_no++;
                break;
            case 'TYPE':
                xmlVarSymb("TYPE", $dom, $programNode, $instruction_no, $instruction_array);
                $instruction_no++;
                break;
            case 'LABEL':
                xmlLabel("LABEL", $dom, $programNode, $instruction_no, $instruction_array);
                $instruction_no++;
                break;
            case 'JUMP':
                xmlLabel("JUMP", $dom, $programNode, $instruction_no, $instruction_array);
                $instruction_no++;
                $GLOBALS['jumps']++;
                break;
            case 'JUMPIFEQ':
                xmlLabelSymb1Symb2("JUMPIFEQ", $dom, $programNode, $instruction_no, $instruction_array);
                $instruction_no++;
                $GLOBALS['jumps']++;
                break;
            case 'JUMPIFNEQ':
                xmlLabelSymb1Symb2("JUMPIFNEQ", $dom, $programNode, $instruction_no, $instruction_array);
                $instruction_no++;
                $GLOBALS['jumps']++;
                break;
            case 'EXIT':
                xmlSymb("EXIT", $dom, $programNode, $instruction_no, $instruction_array);
                $instruction_no++;
                break;
            case 'DPRINT':
                xmlSymb("DPRINT", $dom, $programNode, $instruction_no, $instruction_array);
                $instruction_no++;
                break;
            case 'BREAK':
                xmlNoArg("BREAK", $dom, $programNode, $instruction_no, $instruction_array);
                $instruction_no++;
                break;
            default:
                throw new ParseException("wrong instruction code", 22);
        }
    }

    $GLOBALS['loc'] = $instruction_no - 1;
    $dom->appendChild($programNode);
    return $dom;
}


// the MAIN program

// stats
$comments = 0;
$loc = 0;
$labels = array();
$jumps = 0;


try {
    ParseArguments::getArguments($argv, $argc);
} catch (ParseException $e) {
    exit($e->code);
}

if (ParseArguments::$help) {
    help();
    exit(0);
}

$stdout = fopen('php://stdout', 'w') or die(12);

try {
    $dom = Parse(getLine());
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    fwrite(STDOUT, $dom->saveXML());
} catch (ParseException $e) {
    exit($e->code);
}


if (ParseArguments::$stats) {
    $stats = fopen(ParseArguments::$dir, "w") or die(12);

    foreach (ParseArguments::$order as $o) {
        switch ($o) {
            case "labels":
                fwrite($stats, count($labels) . "\n");
                break;
            case "jumps":
                fwrite($stats, $jumps . "\n");
                break;
            case "loc":
                fwrite($stats, $loc . "\n");
                break;
            case "comments":
                fwrite($stats, $comments . "\n");
                break;
        }
    }
    fclose($stats);
}
