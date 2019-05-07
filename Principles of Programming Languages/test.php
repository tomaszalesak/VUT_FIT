<?php

/**
 * write help to stdout
 */
function help()
{
    print "Použití: php7.3 test.php [--help] [--directory=path] [--recursive] [--parse-script=file] [--int-script=file] [--parse-only] [int-only] [--testlist=file] [--match=regexp]\n";
    print "     --help                  tahle pomoc\n";
    print "     --directory=path        testy bude hledat v zadaném adresáři\n";
    print "     --recursive             testy bude hledat nejen v zadaném adresáři, ale i rekurzivně ve všech jeho podadresářích\n";
    print "     --parse-script=file     soubor se skriptem v PHP 7.3 pro analýzu zdrojového kódu v IPPcode19\n";
    print "     --int-script=file       soubor se skriptem v Python 3.6 pro interpret XML reprezentace kódu v IPPcode19\n";
    print "     --parse-only            bude testován pouze skript pro analýzu zdrojového kódu v IPPcode19\n";
    print "     --int-only              bude testován pouze skript pro interpret XML reprezentace kódu v IPPcode19\n";
    print "     --testlist=file         explicitní zadání seznamu adresářů formou externího souboru file\n";
    print "     --match=regexp          výběr testů, jejichž jméno bez přípony odpovídá zadanému regulárnímu výrazu regexp dle PCRE syntaxe\n";
}

/**
 * creates div element with the name of the ok test
 * @param $name
 * @return string
 */
function ok_test_html($name)
{
    $name = substr($name, 0, -1);
    return "<div class=\"box ok\"><b>$name</b></div>";
}

/**
 * creates div element with the name of the error test
 * @param $name
 * @param $returned
 * @param $expected
 * @param string $msg
 * @return string
 */
function err_test_html($name, $returned, $expected, $msg = "")
{
    $name = substr($name, 0, -1);
    return "<div class=\"box error\"><b>$name</b> Expected: $expected Returned: $returned Message: $msg</div>";
}

/**
 * creates html head
 * @return string
 */
function head_html()
{
    return "<style>
        .box {margin: auto;width: 80%;padding: 10px;}
        .ok {background-color:rgba(11,128,9,0.63);}
        .error {background-color:#ff0020;}
        .head {margin: auto;width: 80%;border: 3px solid green;padding: 10px;}
        </style><title>IPP Tests</title></head>";
}

/**
 * creates title from argument options
 * @param $options
 * @return string
 */
function title_html($options)
{
    if (array_key_exists("parse-only", $options)) {
        return "Parser only tests";
    } elseif (array_key_exists("int-only", $options)) {
        return "Interpreter only tests";
    } else {
        return "Parse and interpreter tests";
    }
}

/**
 * triggers the right test
 * @param $directory_path
 * @param $filename
 */
function test($directory_path, $filename)
{
    global $options;
    $in = FALSE;
    $out = FALSE;
    $rc = FALSE;

    if (file_exists($directory_path . "/" . $filename . "in")) {
        $in = TRUE;
    }
    if (file_exists($directory_path . "/" . $filename . "out")) {
        $out = TRUE;
    }
    if (file_exists($directory_path . "/" . $filename . "rc")) {
        $rc = TRUE;
    }
    if ($in === FALSE) {
        exec("touch " . $directory_path . "/" . $filename . "in");
    }
    if ($out === FALSE) {
        exec("touch " . $directory_path . "/" . $filename . "out");
    }
    if ($rc === FALSE) {
        exec("touch " . $directory_path . "/" . $filename . "rc");
        exec('echo "0" > ' . $directory_path . "/" . $filename . "rc");
    }

    exec('touch ./tmp1');
    exec('touch ./tmp2');

    if (array_key_exists("parse-only", $options)) {
        parse_only($directory_path, $filename);
    } elseif (array_key_exists("int-only", $options)) {
        int_only($directory_path, $filename);
    } else {
        parse_and_int($directory_path, $filename);
    }

    exec('rm ./tmp1');
    exec('rm ./tmp2');

    if ($in === FALSE) {
        exec("rm " . $directory_path . "/" . $filename . "in");
    }
    if ($out === FALSE) {
        exec("rm " . $directory_path . "/" . $filename . "out");
    }
    if ($rc === FALSE) {
        exec("rm " . $directory_path . "/" . $filename . "rc");
    }
}

/**
 * test for parser only
 * @param $directory_path
 * @param $filename
 */
function parse_only($directory_path, $filename)
{
    global $tests;
    global $ok_tests;
    global $nok_tests;
    global $parse_script;
    $correct_rc = exec('cat ' . $directory_path . "/" . $filename . 'rc');
    exec('php7.3 ' . $parse_script . ' < ' . $directory_path . "/" . $filename . 'src > ./tmp1', $output, $rc1);
    if (strval($rc1) !== $correct_rc) {
        // nok
        $tests .= err_test_html($filename, strval($rc1), $correct_rc, "different return values.");
        $nok_tests++;
        return;
    }

    if ($rc1 === 0) {
        exec('java -jar /pub/courses/ipp/jexamxml/jexamxml.jar ./tmp1 ' . $directory_path . "/" . $filename . 'out', $output, $rc2);
        exec("rm ./tmp1.log");
        if ($rc2 === 0) {
            // ok
            $tests .= ok_test_html($filename);
            $ok_tests++;
        } else {
            // nok
            $tests .= err_test_html($filename, strval($rc1), $correct_rc, "xml output files are not same");
            $nok_tests++;
        }
    } else {
        // ok
        $tests .= ok_test_html($filename);
        $ok_tests++;
    }
}

/**
 * test for interpreter only
 * @param $directory_path
 * @param $filename
 */
function int_only($directory_path, $filename)
{
    global $tests;
    global $ok_tests;
    global $nok_tests;
    global $int_script;
    exec('python3.6 ' . $int_script . ' < ' . $directory_path . "/" . $filename . 'src > ./tmp2', $output, $rc1);
    $correct_rc = exec('cat ' . $directory_path . "/" . $filename . 'rc');

    if (strval($rc1) !== $correct_rc) {
        // nok
        $tests .= err_test_html($filename, strval($rc1), $correct_rc, "different return values.");
        $nok_tests++;
        return;
    }

    if ($rc1 === 0) {
        exec('diff ./tmp2 ' . $directory_path . "/" . $filename . 'out', $output, $rc2);
        if ($rc2 === 0) {
            // ok
            $tests .= ok_test_html($filename);
            $ok_tests++;
        } else {
            // nok
            $tests .= err_test_html($filename, strval($rc1), $correct_rc, "output files are not same");
            $nok_tests++;
        }
    } else {
        // ok
        $tests .= ok_test_html($filename);
        $ok_tests++;
    }
}

/**
 * test both parser and interpreter, output from parser to input of interpreter
 * @param $directory_path
 * @param $filename
 */
function parse_and_int($directory_path, $filename)
{
    global $tests;
    global $ok_tests;
    global $nok_tests;
    global $parse_script;
    global $int_script;

    exec('php7.3 ' . $parse_script . ' < ' . $directory_path . "/" . $filename . 'src > ./tmp1', $output, $rc1);
    $correct_rc = exec('cat ' . $directory_path . "/" . $filename . 'rc');

    if ($rc1 !== 0) {
        if (strval($rc1) !== $correct_rc) {
            // nok
            $tests .= err_test_html($filename, strval($rc1), $correct_rc, "different return values. (return value from parser)");
            $nok_tests++;
            return;
        }
    }

    exec('python3.6 ' . $int_script . ' --source=./tmp1 < ' . $directory_path . "/" . $filename . 'in > ./tmp2', $output, $rc2);

    if (strval($rc2) !== $correct_rc) {
        // nok
        $tests .= err_test_html($filename, strval($rc2), $correct_rc, "different return values. (return value from interpreter)");
        $nok_tests++;
        return;
    }

    if ($rc2 === 0) {
        exec('diff ./tmp2 ' . $directory_path . "/" . $filename . 'out', $output, $rc2);
        if ($rc2 === 0) {
            // ok
            $tests .= ok_test_html($filename);
            $ok_tests++;
            return;
        } else {
            // nok
            $tests .= err_test_html($filename, strval($rc2), $correct_rc, "output files are not same");
            $nok_tests++;
            return;
        }
    }
}

/**
 * goes through all available tests and runs them
 * @param $directory_path
 */
function run_tests($directory_path)
{
    global $options;
    global $no_of_tests;

    $dir_handle = opendir($directory_path);
    while ($file = readdir($dir_handle)) {
        if (($file !== ".") && ($file !== "..")) {
            if (is_dir($directory_path . "/" . $file)) {
                if (array_key_exists("recursive", $options)) {
                    run_tests($directory_path . "/" . $file);
                }
            }

            if (is_file($directory_path . "/" . $file)) {
                $extension = pathinfo($file, PATHINFO_EXTENSION);
                $filename = basename($file, $extension);

                if ($extension === 'src') {
                    test($directory_path, $filename);
                    $no_of_tests++;
                }
            }
        }
    }
}


// MAIN

$opts = array(
    "help",             // --help
    "directory::",      // --directory=path
    "recursive",        // --recursive
    "parse-script::",   // --parse-script=file
    "int-script::",     // --int-script=file
    "parse-only",       // --parse-only
    "int-only",         // --int-only
    "testlist",         // --testlist=file
    "match:",           // --match=regexp
);
$options = getopt("", $opts);
//var_dump($options);

if (array_key_exists("help", $options)) {
    help();
    exit(0);
}
if (array_key_exists("parse-only", $options) && array_key_exists("int-only", $options)) {
    // wrong args
    exit(10);
}
if (array_key_exists("testlist", $options) && array_key_exists("directory", $options)) {
    // wrong args
    exit(10);
}

$directory_path = ".";
if (array_key_exists("directory", $options)) {
    if (is_dir($options["directory"])) {
        $directory_path = $options["directory"];
    } else {
        // wrong dir
        die(11);
    }
}

$parse_script = "parse.php";
if (array_key_exists("parse-script", $options)) {
    if (is_file($options["parse-script"])) {
        $parse_script = $options["parse-script"];
    } else {
        // wrong file
        die(11);
    }
}

$int_script = "interpret.py";
if (array_key_exists("int-script", $options)) {
    if (is_file($options["int-script"])) {
        $int_script = $options["int-script"];
    } else {
        // wrong file
        die(11);
    }
}

$no_of_tests = 0;
$ok_tests = 0;
$nok_tests = 0;

$head = head_html();
$title = title_html($options);
$tests = "<h3 class='head'>$title</h3>";
run_tests($directory_path);
$down = "<div class='head'><h3>All tests: $no_of_tests</h3><h3>OK: $ok_tests</h3><h3>ERRORS: $nok_tests</h3></div>";
$html = "<!DOCTYPE html><html lang='en'>$head<body>$tests $down</body></html>";
fwrite(STDOUT, $html);
exit(0);