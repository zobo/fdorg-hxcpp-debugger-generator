<?php

$types = array("Int"=>"int","Bool"=>"bool","String"=>"string","Command"=>"Command");
$lines = file("commands.txt");
foreach ($lines as $line) {
        $line = trim($line);
        $line = str_replace("unsafe", "unsafe_", $line);
        if ($line == "") continue;
        if (substr($line,0,2) == "//") {
                echo $line."\n";
                continue;
        }
        $args = array();
        if (preg_match('/(.*)\((.*)\);/', $line, $m))
        {
                $c = $m[1];
                $tmp = array_map("trim",explode(",", $m[2]));
                foreach ($tmp as $arg) {
                        list($n,$t) = explode(":", $arg);
                        $n = trim($n);
                        $t = trim($t);
                        $t2 = $types[$t];
                        if (!$t2) die("unknown type $t\n");
                        $args[] = array($n,$t,$t2);
                }
        }
        else if (preg_match('/(.*);/', $line, $m))
        {
                $c = $m[1];
        }
        else
        {
                die("bac match ".$line."\n");
        }

        $tmp = array();
        foreach ($args as $arg) {
                $tmp[] = $arg[2]." ".$arg[0];
        }
        echo "\t\t".'public static Command '.$c.'('.implode(", ",$tmp).') { Command cmd = new Command("'.$c.'"); ';
        foreach ($args as $arg) {
                echo 'cmd.arguments.Add('.$arg[0].'); ';
        }
        echo 'return cmd; }'."\n";

}
