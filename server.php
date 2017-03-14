<?php

$socket = stream_socket_server("tcp://0.0.0.0:8000", $errno, $errstr);

if (!$socket) {
    die("$errstr ($errno)\n");
}

$connects = array();
while (true) {
    //формируем массив прослушиваемых сокетов:
    $read = $connects;
    $read []= $socket;
    $write = $except = null;

    if (!stream_select($read, $write, $except, null)) {//ожидаем сокеты доступные для чтения (без таймаута)
        break;
    }

    if (in_array($socket, $read)) {//есть новое соединение
        //принимаем новое соединение и производим рукопожатие:
        if (($connect = stream_socket_accept($socket, -1))) {
            $connects[] = $connect;//добавляем его в список необходимых для обработки
            onOpen($connect, $info);//вызываем пользовательский сценарий
        }
        unset($read[ array_search($socket, $read) ]);
    }

    $read_data = [];
    foreach($read as $connect) {//обрабатываем все соединения
        $data = fread($connect, 100000);

        if (!$data) { //соединение было закрыто
            fclose($connect);
            unset($connects[ array_search($connect, $connects) ]);
            onClose($connect);//вызываем пользовательский сценарий
            continue;
        }

        $name = stream_socket_get_name($connect, true);
        $read_data[$name] = $data;
    }

    foreach($connects as $connect) {
        foreach($read_data as $name => $data) {
            fwrite($connect, "$name - $data");
        }
    }
}

fclose($server);


function onOpen($connect, $info) {
    echo "open\n";
    fwrite($connect, "Hello\n");
}

function onClose($connect) {
    echo "close\n";
}
