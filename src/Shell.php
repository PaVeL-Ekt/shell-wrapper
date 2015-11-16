<?php

namespace PavelEkt\Wrappers;

use PavelEkt\BaseComponents\abstracts\BaseComponent;
/**
 * Класс для запуска приложений, с указанием рабочего каталога.
 */
class Shell extends BaseComponent
{
    /**
     * @var string $workDirectory Текущая рабочая директория.
     */
    public $workDirectory = '';

    /**
     * @var mixed[] $lastResult Результат выполнения последней операциию.
     */
    protected $lastResult = [
        'command' => '',
        'retval' => 0,
        'output' => [
            'stdout' => [],
            'stderr' => []
        ]
    ];

    /**
     * @var array $listeners Список слушателей, которые будут получать вывод из стандартных потоков ввывода и ошибок.
     */
    protected $listeners = [];

    public function __construct($workDirectory = null)
    {
        !empty($workDirectory) && !$this->cd($workDirectory) && $this->workDirectory = '/';
    }

    /**
     * Чтение указателя конца потока.
     *
     * Используется для функции безопасного чтения потока.
     *
     * @param resource &$stream Поток, который читаем.
     * @param integer $start Переменная, в которую складываем текущее время.
     * @return bool
     */
    protected function saveFeof(&$stream, &$start)
    {
        $start = microtime(true);
        return feof($stream);
    }

    /**
     * Безопасное чтение потока.
     *
     * Данный метод сделан, для обхода бесконечного зацикливания, при неправильном завершении вызываемого процесса.
     * (функция EOF всегда начинает возвращать false).
     *
     * @param resource &$stream Поток, из которого читаем.
     * @param string &$data Переменная в которую складываем.
     * @param float $timeout Время таймаута в секундах.
     * @return bool
     */
    protected function readStreamContent(&$stream, &$data, $timeout = 300.00)
    {
        $readStart = microtime(true);
        $feofTime = null;

        while (!$this->saveFeof($stream, $feofTime) && ($feofTime - $readStart) < $timeout) {
            $buf = fgets($stream, 1024);
            $data .= $buf;
            if (!empty($this->listeners)) {
                foreach($this->listeners as $object) {
                    $object->shellListener($this, $buf);
                }
            }
        }
        if ((microtime(true) - $readStart) > $timeout) {
            return false;
        }
        return true;
    }

    /**
     * Запускает команду оболочки в рабочем каталоге $this->workDirectory.
     *
     * @param string $cmd Запускаемая команда.
     * @param mixed[] &$output Если передана переменная, заполнит ее выводом команды.
     * @param mixed[] $env Переменные окружения.
     * @return integer Код возврата.
     */
    public function exec($cmd, &$output = null, $env = [])
    {
        $this->lastResult['command'] = $cmd;

        $descriptorSpec = array(
            0 => array("pipe", "r"),
            1 => array("pipe", "w"),
            2 => array("pipe", "w"),
        );

        $process = proc_open($cmd, $descriptorSpec, $pipes, $this->workDirectory, $env);
        if (is_resource($process)) {
            fclose($pipes[0]);

            $outputPrepare = '';

            $result = $this->readStreamContent($pipes[1], $outputPrepare);
            if (!$result) {
                $outputPrepare .= PHP_EOL . 'Process exiting by timeout.';
            }
            fclose($pipes[1]);

            $errorPrepare = '';

            $this->readStreamContent($pipes[2], $errorPrepare);
            fclose($pipes[2]);

            $outputPrepare = rtrim($outputPrepare, "\n");
            $errorPrepare = rtrim($errorPrepare, "\n");

            $this->lastResult['output'] = [
                'stdout' => !empty($outputPrepare) ? explode("\n", $outputPrepare) : [],
                'stderr' => !empty($errorPrepare) ? explode("\n", $errorPrepare) : []
            ];

            $this->lastResult['retval'] = (int) proc_close($process);
            if (!$result) {
                $this->lastResult['retval'] = 1;
            }
        } else {
            $this->lastResult['retval'] = -1;
            $this->lastResult['output'] = [
                'stdout' => [],
                'stderr' => ['Не смог запустить команду: ' . $cmd],
            ];
        }

        if (isset($output)) {
            $output = $this->lastResult['output'];
        }
        return $this->lastResult['retval'];
    }

    /**
     * Возвращает результат последнего запуска.
     *
     * @return mixed[]
     * Вернет массив типа:
     * '''php
     * [
     *     // Последняя выполненая команда
     *     'command' => [
     *         'ls'
     *     ],
     *     // Код завершения запущенного процесса
     *     'retval' => [
     *         0
     *     ],
     *     // Данные стандартных потоков
     *     'output' => [
     *         // Поток вывода
     *         'stdout' => [
     *         ],
     *         // Поток ошибок
     *         'stderr' => [
     *         ]
     *     ]
     * ]
     * '''
     */
    public function getLastResult()
    {
        return $this->lastResult;
    }

    /**
     * Переходит по указанному пути.
     *
     * Пути могут задаватся в полном соответствии с правилами оболочки.
     *
     * @param string $path путь, по которому необходимо перейти.
     * @return bool
     */
    public function cd($path = '/')
    {
        if ($path[0] == '/') {
            $realPath = realpath($path);
        } else {
            $realPath = realpath($this->workDirectory . '/' . $path);
        }

        if ($realPath !== false) {
            $out = [];
            if ($this->exec('cd ' . $realPath, $out) === 0) {
                $this->workDirectory = $realPath;
                return true;
            }
        }
        return false;
    }

    /**
     * Подписка объекта для получения данных
     * @param $name Имя слушателя.
     * @param $object метод слушателя.
     */
    public function subscribeObject($name, $object)
    {
        if (is_object($object) && method_exists($object, 'shellListener')) {
            $this->listeners[$name] = $object;
        }
    }

    /**
     * Отписка объекта
     * @param $name Имя слушателя
     */
    public function unsubscribeObject($name)
    {
        if (isset($this->listeners[$name])) {
            unset ($this->listeners[$name]);
        }
    }
}
