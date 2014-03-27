<?php

define("TAB","    ");
define("NL","\n");

$types = array("Int"=>"int","Bool"=>"bool","String"=>"string","Command"=>"Command","Array<String>"=>"String[]");
$lines = file("message.txt");
$tmp_buf = "";
foreach ($lines as $line) {
        $line = $tmp_buf.trim($line);
        $tmp_buf = "";
        if ($line == "") continue;
        if (substr($line,0,2) == "//") {
                //echo $line."\n";
                continue;
        }
        if (substr($line,0,2) == "/*") {
                $in_comment = true;
                continue;
        }
        if ($in_comment && substr($line,-2) == "*/") {
                $in_comment = false;
                continue;
        }
        if ($in_comment) {
                continue;
        }

        if (preg_match('/enum (.*)/', $line, $m))
        {
                $enum = $m[1];
                $ctors = array();
                continue;
        }

        if ($line == "{") continue;
        if ($line == "}")
        {
                echo TAB.'class '.$enum.NL;
                echo TAB.'{'.NL;
                echo TAB.TAB.'public static '.$enum.' FromEnum(HaxeEnum haxeEnum)'.NL;
                echo TAB.TAB.'{'.NL;
                echo TAB.TAB.TAB.'if (haxeEnum.name != "org.flashdevelop.cpp.debugger.'.$enum.'") { throw new InvalidCastException("Trying to cast HaxeEnum "+haxeEnum.name+" to org.flashdevelop.cpp.debugger.Message"); }'.NL;
                foreach ($ctors as $ctor)
                {
                        echo TAB.TAB.TAB.'if (haxeEnum.constructor == "'.$ctor['name'].'") {'.NL;
                        echo TAB.TAB.TAB.TAB.$ctor['name'].' ret = new '.$ctor['name'].'();'.NL;
                        foreach ($ctor['args'] as $n=>$arg)
                        {

                                echo TAB.TAB.TAB.TAB.'ret.'.$arg[0].' = ';
                                if ($arg[1] == "Array<String>")
                                {
                                        echo 'Array.ConvertAll<Object, String>((Object[])haxeEnum.arguments['.$n.'], item => (String)item);'.NL;
                                }
                                else if ($arg[2])
                                {
                                        echo '('.$arg[2].')haxeEnum.arguments['.$n.'];'.NL;
                                }
                                else
                                {
                                        echo $arg[1].'.FromEnum((HaxeEnum)haxeEnum.arguments['.$n.']);'.NL;
                                }
                        }
                        echo TAB.TAB.TAB.TAB.'return ret;'.NL;

                        echo TAB.TAB.TAB.'}'.NL;
                }
                echo TAB.TAB.TAB.'throw new InvalidCastException("Unknown constructor "+haxeEnum.constructor+" for HaxeEnum "+haxeEnum.name);'.NL;
                echo TAB.TAB.'}'.NL;
                echo NL;

                foreach ($ctors as $ctor)
                {
                        echo TAB.TAB.'public class '.$ctor['name'].' : '.$enum.NL;
                        echo TAB.TAB.'{'.NL;
                        // stbcla
                        foreach ($ctor['args'] as $n=>$arg)
                        {
                                echo TAB.TAB.TAB.'public ';
                                if ($arg[2]) echo $arg[2]; else echo $arg[1];
                                echo ' '.$arg[0].' { get; set; }'.NL;
                        }
                        echo TAB.TAB.TAB.'public override string ToString()'.NL;
                        echo TAB.TAB.TAB.'{'.NL;
                        echo TAB.TAB.TAB.TAB.'return "['.$enum.'.'.$ctor['name'].'(';
                        foreach ($ctor['args'] as $n=>$arg)
                        {
                                if ($n>0) echo ', ';
                                if ($arg[1] == "Array<String>")
                                {
                                        echo $arg[0].'=string[" + String.Join(", ", '.$arg[0].') + "]';
                                }
                                else
                                {
                                        echo $arg[0].'=" + '.$arg[0].' + "';
                                }
                        }
                        echo ')]";'.NL;
                        echo TAB.TAB.TAB.'}'.NL;
                        echo TAB.TAB.'}'.NL;
                        echo NL;
                }

                echo TAB.'}'.NL.NL;
                // class
                // static factory
                // subclass wiith auto getset
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
                        if ($n == "string") $n = "string_";
                        $t = trim($t);
                        $t2 = $types[$t];
                        //if (!$t2) die("unknown type $t\n");
                        $args[] = array($n,$t,$t2);
                }
        }
        else if (preg_match('/(.*);/', $line, $m))
        {
                $c = $m[1];
        }
        else if (substr($line,-1) != ";")
        {
                // splitted line
                $tmp_buf = $line." ";
                continue;
        }
        else
        {
                die("bad match ".$line."\n");
        }
        $ctors[] = array("name"=>$c,"args"=>$args);

        /*
        $tmp = array();
        foreach ($args as $arg) {
                $tmp[] = $arg[2]." ".$arg[0];
        }
        echo "\t\t".'public static Command '.$c.'('.implode(", ",$tmp).') { Command cmd = new Command("'.$c.'"); ';
        foreach ($args as $arg) {
                echo 'cmd.arguments.Add('.$arg[0].'); ';
        }
        echo 'return cmd; }'."\n";
        */
        //echo $enum." ".$c."\n";

}
