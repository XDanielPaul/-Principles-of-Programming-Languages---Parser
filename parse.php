<?php

    error_reporting(0);
    
    /* --help */
    if (($argc == 2) && $argv[1] == '--help'){
            print "This script converts 3-address instructions into XML file. Use with php parse.php < file.in > file.xml";
            exit(0);
    } else if ($argc == 2){ 
            exit(10);
    }

    /* Checking if header is correct */
    if(!$line = fgets(STDIN)){
        fputs(STDERR, "No input! - Invalid or missing header!");
        exit(21);
    } 

    $line = trim(preg_replace('/\s\s+/', ' ',strtolower(str_replace(' ','', $line))));
    while($line[0] == '#'){
        $line = fgets(STDIN);
        $line = trim(preg_replace('/\s\s+/', ' ',strtolower(str_replace(' ','', $line))));
    }
    $line = preg_replace('/#.*/', '', $line );
    $line = trim(preg_replace('/\s\s+/', ' ',strtolower(str_replace(' ','', $line))));

    if ($line != '.ippcode20'){
        fputs(STDERR, "Invalid or missing header!");
        exit(21);
    }


    /* Creating XML file */
    $xw = xmlwriter_open_memory();
    xmlwriter_set_indent($xw, 1);
    $res = xmlwriter_set_indent_string($xw, '  ');
    xmlwriter_start_document($xw, '1.0','utf-8');
    xmlwriter_start_element($xw,'program');
    xmlwriter_start_attribute($xw,'language');
    xmlwriter_text($xw, 'IPPcode20');

    $order = 1;

    /* Loading input */ 
    while ($line = fgets(STDIN)){
        /* Stripping lines of comments */
        $line = preg_replace('/#.*/', '', $line );
        /* If a line was not only a comment */
        if ($line[0] != "\n" && $line[0] != ''){
            /* Splitting line into parts */
            $line=str_replace("\n","",$line);
            $line = explode(" ", $line);
            /* This seems to work :D -> Trims all empty elements of an array */
            $line = array_filter($line, function($a) {
                return trim($a) !== "";
            });

            /* Creating instruction tag in XML format */
            xmlwriter_start_element($xw,'instruction');
            xmlwriter_start_attribute($xw,'order');
            xmlwriter_text($xw, $order);
            xmlwriter_end_attribute($xw);
            xmlwriter_start_attribute($xw,'opcode');
            xmlwriter_text($xw, $line[0]);
            xmlwriter_end_attribute($xw);
            /* Resolving arguments */
            argsToXML($line, $xw);
            xmlwriter_end_element($xw);
        
            $order++;
        }
        
    }
    /* Ending XML file creation and exiting correctly */
    xmlwriter_end_element($xw);
    xmlwriter_end_document($xw);
    echo xmlwriter_output_memory($xw);
    exit(0);

    /* Checks for correct number of arguments and resolves their implementation in XML */
    function argsToXML($line, $xw){
        $argcount = 1;
        $line[0] = strtoupper($line[0]);
        switch($line[0]) {
            case 'DEFVAR':
            case 'POPS':
                checkArgNum(count($line), 2);
                varToXML($line,$xw, $argcount);
                break;
            case 'MOVE':
            case 'STRLEN':
            case 'TYPE':
            case 'INT2CHAR':
            case 'NOT':
                checkArgNum(count($line), 3);
                varToXML($line,$xw, $argcount);
                $argcount++;
                symbToXML($line,$xw, $argcount);
                break;
            case 'LABEL':
            case 'JUMP':
            case 'EXIT':
            case 'CALL':
                checkArgNum(count($line), 2); 
                label_jump_ToXML($line, $xw);
                break;
            case 'WRITE':
            case 'PUSHS':
            case 'DPRINT':
                checkArgNum(count($line), 2);
                symbToXML($line,$xw, $argcount);
                break;
            case 'JUMPIFEQ':
            case 'JUMPIFNEQ':
                checkArgNum(count($line), 4);
                label_jump_ToXML($line, $xw);
                $argcount++;
                symbToXML($line,$xw, $argcount);
                $argcount++;
                symbToXML($line,$xw, $argcount);
                break;
            case 'GETCHAR':
            case 'SETCHAR':
            case 'CONCAT':
            case 'STRI2INT':
            case 'AND':
            case 'OR':
            case 'LT':
            case 'GT':
            case 'EQ':
            case 'IDIV':
            case 'MUL':
            case 'SUB':
            case 'ADD':
                checkArgNum(count($line), 4);
                varToXML($line,$xw, $argcount);
                $argcount++;
                symbToXML($line,$xw, $argcount);
                $argcount++;
                symbToXML($line,$xw, $argcount);
                break;
            case 'READ':
                checkArgNum(count($line), 3);
                varToXML($line,$xw, $argcount);
                $argcount++;
                typeToXML($line,$xw, $argcount);
                break;
            case 'CREATEFRAME':
            case 'PUSHFRAME': 
            case 'POPFRAME':
            case 'RETURN':
            case 'BREAK':
                checkArgNum(count($line), 1);
                break;
            default:
                print_r($line);
                fputs(STDERR, "Invalid instruction!");
                exit(22);
                break;
                
        }
    }


    /* Resloves type=type */
    function typeToXML($line, $xw, $argcount){
        xmlwriter_start_element($xw,'arg'.$argcount);
        xmlwriter_start_attribute($xw, 'type');
        xmlwriter_text($xw, 'type');
        xmlwriter_end_attribute($xw);
        $line[$argcount] = trim(preg_replace('/\s\s+/', '', $line[$argcount]));
        switch ($line[$argcount]) {
            case 'string':
            case 'int':
            case 'bool':
                break;
            default: 
                fputs(STDERR, "Invalid type!");
                exit(23);
        }
        xmlwriter_text($xw, $line[$argcount]);
        xmlwriter_end_element($xw);
    }

    /* Resloves type=var */
    function varToXML($line, $xw, $argcount){
        xmlwriter_start_element($xw,'arg'.$argcount);
        xmlwriter_start_attribute($xw, 'type');
        xmlwriter_text($xw, 'var');
        xmlwriter_end_attribute($xw);
        $line[$argcount] = trim(preg_replace('/\s\s+/', '', $line[$argcount]));
        xmlwriter_text($xw, $line[$argcount]);
        xmlwriter_end_element($xw);
    }

    /* Resloves type=symbol */
    function symbToXML($line, $xw, $argcount){

        $result = process_symb($line,$argcount);
        
        xmlwriter_start_element($xw,'arg'.$argcount);
        xmlwriter_start_attribute($xw, 'type');
        xmlwriter_text($xw, $result[0]);
        xmlwriter_end_attribute($xw);
        $result[1] = trim(preg_replace('/\s\s+/', '', $result[1]));
        if ($result[1] != ''){
            xmlwriter_text($xw, $result[1]);
        }
        xmlwriter_end_element($xw);
    }

    /* Resloves type=label */
    function label_jump_ToXML($line, $xw){
        xmlwriter_start_element($xw,'arg1');
        xmlwriter_start_attribute($xw, 'type');
        xmlwriter_text($xw, 'label');
        xmlwriter_end_attribute($xw);
        $line[1] = trim(preg_replace('/\s\s+/', '', $line[1]));
        if (!preg_match('/^[A-Za-z_\-$&%\*\!\?][A-Za-z0-9_\-$&%\*\!\?]*$/', $line[1])){
            fputs(STDERR, 'Invalid label name!'); 
            exit(23);
        }
        xmlwriter_text($xw, $line[1]);
        xmlwriter_end_element($xw);
    }

    /* Checks for lexical and syntactic correctness of a symbol */
    function process_symb($line, $argcount){
        $arr = explode('@' ,$line[$argcount], 2);
        $type = trim($arr[0]);
        $text = trim($arr[1]);
        switch($type){
            case 'string':

                if (preg_match_all('/(?!\\\\[\d]{3})[\\\\#\s]/m', $text, $matches, PREG_SET_ORDER, 0)){
                    fputs(STDERR, 'Invalid string!'); 
                    exit(23);
                }
                break;
            case 'int':
                if (!preg_match('/^[-+]?\d+$/', $text)){
                    fputs(STDERR, 'Invalid integer!'); 
                    exit(23);
                }
                break;
            case 'bool':
                if($text != 'true' && $text != 'false'){
                    fputs(STDERR, 'Invalid bool type!'); 
                    exit(23);
                }
                break;
            case 'nil':
                if ($text != "nil"){
                    fputs(STDERR, 'Invalid nil!'); 
                    exit(23);
                }
                break;
            case 'TF':
            case 'LF':
            case 'GF':
                if (!preg_match('/^[A-Za-z_\-$&%\*\!\?][A-Za-z0-9_\-$&%\*\!\?]*$/', $text)){
                    fputs(STDERR, 'Invalid var name!'); 
                    exit(23);
                }
                $type = 'var';
                $text = $line[$argcount];
                break;
            default:
                fputs(STDERR, "Invalid type!");
                exit(23);
                break;
        }
        
        return array($type,$text);
    }

    /* Checks for correct number of agruments */
    function checkArgNum($num1, $num2){
        if($num1 != $num2){
            fputs(STDERR, 'Wrong number of arguments!'); 
            exit(23);
        }
    }

?> 