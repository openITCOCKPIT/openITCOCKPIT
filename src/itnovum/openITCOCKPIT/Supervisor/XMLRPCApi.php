<?php
// Copyright (C) 2015-2025  it-novum GmbH
// Copyright (C) 2025-today Allgeier IT Services GmbH
//
// This file is dual licensed
//
// 1.
//     This program is free software: you can redistribute it and/or modify
//     it under the terms of the GNU General Public License as published by
//     the Free Software Foundation, version 3 of the License.
//
//     This program is distributed in the hope that it will be useful,
//     but WITHOUT ANY WARRANTY; without even the implied warranty of
//     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//     GNU General Public License for more details.
//
//     You should have received a copy of the GNU General Public License
//     along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
// 2.
//     If you purchased an openITCOCKPIT Enterprise Edition you can use this file
//     under the terms of the openITCOCKPIT Enterprise Edition license agreement.
//     License agreement and license key will be shipped with the order
//     confirmation.

namespace App\itnovum\openITCOCKPIT\Supervisor;

/**
 * Helper class for easy communication with the Supervisor XML-RPC API
 * Please notice: This class is a Wrapper for the API. Not all API methods are implemented.
 *
 * If you are missing methods, please see the docs and implement the missing method:
 * http://supervisord.org/api.htm
 *
 * This class does not make use of any __call() Magic Methods. This makes sure that the IDE knows all
 * methods and can provide useful autocomplete and knows about method signatures
 */
class XMLRPCApi {

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $url;

    /**
     * Connection timeout in seconds
     * @var
     */
    private $timeout;

    /**
     * @param string $username
     * @param string $password
     * @param string $url Full API url with protocol and port like http://127.0.0.1:9001/RPC2
     */
    public function __construct(string $username, string $password, string $url = 'http://127.0.0.1:9001/RPC2', $timeout = 60) {
        $this->username = $username;
        $this->password = $password;
        $this->url = $url;
        $this->timeout = $timeout;
    }

    /**
     * Send request to the XML-PRC API of Supervisor
     *
     * @param string $functionName API Method name you want to call
     * @param mixed $functionArgs Args you want to pass to the API
     * @return mixed|string
     * @throws ApiException
     */
    public function request(string $functionName, $functionArgs = null) {
        $request = xmlrpc_encode_request($functionName, $functionArgs);
        $header = [];
        $header[] = 'Authorization: Basic ' . base64_encode($this->username . ':' . $this->password);
        $header[] = "Content-type: text/xml";
        $header[] = "Content-length: " . strlen($request);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, false);
        curl_setopt($ch, CURLOPT_PROXY, '');

        $data = curl_exec($ch);

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($status !== 200) {
            throw new ApiException(sprintf('HTTP status code was: %s', $status));
        }

        if (curl_errno($ch)) {
            throw new ApiException(curl_error($ch));
        }

        curl_close($ch);
        return xmlrpc_decode($data);
    }

    /**
     * Return the version of the RPC API used by supervisord
     *
     * @see http://supervisord.org/api.html#supervisor.rpcinterface.SupervisorNamespaceRPCInterface.getAPIVersion
     * @return string version id
     * @throws ApiException
     */
    public function getApiVersion() {
        return $this->request('supervisor.getAPIVersion');
    }

    /**
     * Return the version of the supervisor package in use by supervisord
     *
     * @see http://supervisord.org/api.html#supervisor.rpcinterface.SupervisorNamespaceRPCInterface.getSupervisorVersion
     * @return string version id
     * @throws ApiException
     */
    public function getSupervisorVersion() {
        return $this->request('supervisor.getSupervisorVersion');
    }

    /**
     * Return identifying string of supervisord
     *
     * @see http://supervisord.org/api.html#supervisor.rpcinterface.SupervisorNamespaceRPCInterface.getIdentification
     * @return string identifier identifying string
     * @throws ApiException
     */
    public function getIdentification() {
        return $this->request('supervisor.getIdentification');
    }

    /**
     * Return current state of supervisord as a struct
     *
     * | statecode | statename  | Description                                    |
     * |-----------|------------|------------------------------------------------|
     * | 2         | FATAL      | Supervisor has experienced a serious error.    |
     * | 1         | RUNNING    | Supervisor is working normally.                |
     * | 0         | RESTARTING | Supervisor is in the process of restarting.    |
     * | -1        | SHUTDOWN   | Supervisor is in the process of shutting down. |
     *
     * Return
     * [
     *     'statecode' => (int) 1,
     *     'statename' => 'RUNNING'
     * ]
     *
     * @see http://supervisord.org/api.html#supervisor.rpcinterface.SupervisorNamespaceRPCInterface.getState
     * @return array A struct with keys int statecode, string statename
     * @throws ApiException
     */
    public function getState() {
        return $this->request('supervisor.getState');
    }

    /**
     * Return the PID of supervisord
     *
     * @see http://supervisord.org/api.html#supervisor.rpcinterface.SupervisorNamespaceRPCInterface.getPID
     * @return int PID
     * @throws ApiException
     */
    public function getPID() {
        return $this->request('supervisor.getPID');
    }

    /**
     * Read length bytes from the main log starting at offset
     *
     * | Offset           | Length   | Behavior of readProcessLog                                                     |
     * |------------------|----------|--------------------------------------------------------------------------------|
     * | Negative         | Not Zero | Bad arguments. This will raise the fault BAD_ARGUMENTS.                        |
     * | Negative         | Zero     | This will return the tail of the log, or offset number of characters from the  |
     * |                  |          | end of the log. For example, if offset = -4 and length = 0, then the last four |
     * |                  |          |characters will be returned from the end of the log.                            |
     * | Zero or Positive | Negative | Bad arguments. This will raise the fault BAD_ARGUMENTS.                        |
     * | Zero or Positive | Zero     | All characters will be returned from the offset specified.                     |
     * | Zero or Positive | Positive | A number of characters length will be returned from the offset.                |
     *
     *
     * @see http://supervisord.org/api.html#supervisor.rpcinterface.SupervisorNamespaceRPCInterface.readLog
     *
     * @param int $offset offset to start reading from.
     * @param int $length number of bytes to read from the log
     * @return string result Bytes of log
     * @throws ApiException
     */
    public function readLog(int $offset = 0, int $length = 1024) {
        return $this->request('supervisor.readLog', [$offset, $length]);
    }

    /**
     * Clear the main log
     *
     * @see http://supervisord.org/api.html#supervisor.rpcinterface.SupervisorNamespaceRPCInterface.clearLog
     * @return bool result always returns True unless error
     * @throws ApiException
     */
    public function clearLog() {
        return $this->request('supervisor.clearLog');
    }

    /**
     * Shut down the supervisor process
     *
     * @see http://supervisord.org/api.html#supervisor.rpcinterface.SupervisorNamespaceRPCInterface.shutdown
     * @return bool result always returns True unless error
     * @throws ApiException
     */
    public function shutdown() {
        return $this->request('supervisor.shutdown');
    }

    /**
     * Restart the supervisor process
     *
     * @see http://supervisord.org/api.html#supervisor.rpcinterface.SupervisorNamespaceRPCInterface.restart
     * @return bool result always return True unless error
     * @throws ApiException
     */
    public function restart() {
        return $this->request('supervisor.restart');
    }

    /**
     * Return an array listing the available method names
     *
     * @see http://supervisord.org/api.html#supervisor.xmlrpc.SystemNamespaceRPCInterface.listMethods
     * @return array result An array of method names available (strings).
     * @throws ApiException
     */
    public function listMethods() {
        return $this->request('system.listMethods');
    }

    /**
     * Return a string showing the method’s documentation
     *
     * @see http://supervisord.org/api.html#supervisor.xmlrpc.SystemNamespaceRPCInterface.methodHelp
     * @param string $method The name of the method
     * @return string result The documentation for the method name.
     * @throws ApiException
     */
    public function methodHelp(string $method) {
        return $this->request('system.methodHelp', [$method]);
    }

    /**
     * Return an array describing the method signature in the form [rtype, ptype, ptype…] where rtype is
     * the return data type of the method, and ptypes are the parameter data types that the method accepts
     * in method argument order.
     *
     * @see http://supervisord.org/api.html#supervisor.xmlrpc.SystemNamespaceRPCInterface.methodSignature
     * @param string $method The name of the method
     * @return array result The result.
     * @throws ApiException
     */
    public function methodSignature(string $method) {
        return $this->request('system.methodSignature', [$method]);
    }

    /**
     * Get info about a process named name
     *
     * Returns
     * [
     *     'name' => 'sudo_server',
     *     'group' => 'sudo_server',
     *     'start' => (int) 1683792729,
     *     'stop' => (int) 0,
     *     'now' => (int) 1683792818,
     *     'state' => (int) 200,
     *     'statename' => 'FATAL',
     *     'spawnerr' => 'unknown error making dispatchers for 'sudo_server': ENXIO',
     *     'exitstatus' => (int) 0,
     *     'logfile' => '/dev/stdout',
     *     'stdout_logfile' => '/dev/stdout',
     *     'stderr_logfile' => '',
     *     'pid' => (int) 0,
     *     'description' => 'unknown error making dispatchers for 'sudo_server': ENXIO'
     * ]
     *
     * Process States
     * | Statename | Code | Description                                                                             |
     * |-----------|------|-----------------------------------------------------------------------------------------|
     * | STOPPED   | 0    | The process has been stopped due to a stop request or has never been started.           |
     * | STARTING  | 10   | The process is starting due to a start request.                                         |
     * | RUNNING   | 20   | The process is running.                                                                 |
     * | BACKOFF   | 30   | The process entered the STARTING state but subsequently exited too quickly              |
     * |           |      | (before the time defined in startsecs) to move to the RUNNING state.                    |
     * | STOPPING  | 40   | The process is stopping due to a stop request.                                          |
     * | EXITED    | 100  | The process exited from the RUNNING state (expectedly or unexpectedly).                 |
     * | FATAL     | 200  | The process could not be started successfully.                                          |
     * | UNKNOWN   | 1000 | The process is in an unknown state (supervisord programming error).                     |
     *
     * @see http://supervisord.org/api.html#supervisor.rpcinterface.SupervisorNamespaceRPCInterface.getProcessInfo
     * @param string $name The name of the process (or ‘group:name’)
     * @return array  result A structure containing data about the process
     * @throws ApiException
     */
    public function getProcessInfo(string $name) {
        return $this->request('supervisor.getProcessInfo', [$name]);
    }

    /**
     * Get info about all processes
     *
     * @see http://supervisord.org/api.html#supervisor.rpcinterface.SupervisorNamespaceRPCInterface.getAllProcessInfo
     * @return array result An array of process status results
     * @throws ApiException
     */
    public function getAllProcessInfo() {
        return $this->request('supervisor.getAllProcessInfo');
    }

    /**
     * Start a process
     *
     * @see http://supervisord.org/api.html#supervisor.rpcinterface.SupervisorNamespaceRPCInterface.startProcess
     * @param string $name Process name (or group:name, or group:*)
     * @param boolean $wait Wait for process to be fully started
     * @return boolean result Always true unless error
     * @throws ApiException
     */
    public function startProcess(string $name, bool $wait = true) {
        return $this->request('supervisor.startProcess', [$name, $wait]);
    }

    /**
     * Start all processes listed in the configuration file
     *
     * @see http://supervisord.org/api.html#supervisor.rpcinterface.SupervisorNamespaceRPCInterface.startAllProcesses
     * @param boolean $wait Wait for each process to be fully started
     * @return array result An array of process status info structs
     * @throws ApiException
     */
    public function startAllProcesses($wait = true) {
        return $this->request('supervisor.startAllProcesses', [$wait]);
    }

    /**
     * Start all processes in the group named ‘name’
     *
     * @see http://supervisord.org/api.html#supervisor.rpcinterface.SupervisorNamespaceRPCInterface.startProcessGroup
     * @param string $name The group name
     * @param boolean $wait Wait for each process to be fully started
     * @return array result An array of process status info structs
     * @throws ApiException
     */
    public function startProcessGroup(string $name, $wait = true) {
        return $this->request('supervisor.startProcessGroup', [$name, $wait]);
    }

    /**
     * Stop a process named by name
     *
     * @see http://supervisord.org/api.html#supervisor.rpcinterface.SupervisorNamespaceRPCInterface.stopProcess
     * @param string $name The name of the process to stop (or ‘group:name’)
     * @param boolean $wait Wait for the process to be fully stopped
     * @return boolean result Always return True unless error
     * @throws ApiException
     */
    public function stopProcess(string $name, $wait = true) {
        return $this->request('supervisor.stopProcess', [$name, $wait]);
    }

    /**
     * Stop all processes in the process list
     *
     * @see http://supervisord.org/api.html#supervisor.rpcinterface.SupervisorNamespaceRPCInterface.stopAllProcesses
     * @param boolean $wait Wait for each process to be fully stopped
     * @return array result An array of process status info structs
     * @throws ApiException
     */
    public function stopAllProcesses($wait = true) {
        return $this->request('supervisor.stopAllProcesses', [$wait]);
    }

    /**
     * Stop all processes in the process group named ‘name’
     *
     * @see http://supervisord.org/api.html#supervisor.rpcinterface.SupervisorNamespaceRPCInterface.stopProcessGroup
     * @param string $name The group name
     * @param boolean $wait Wait for each process to be fully stopped
     * @return array result An array of process status info structs
     * @throws ApiException
     */
    public function stopProcessGroup(string $name, $wait = true) {
        return $this->request('supervisor.stopProcessGroup', [$name, $wait]);
    }

    /**
     * Send an arbitrary UNIX signal to the process named by name
     *
     * @see http://supervisord.org/api.html#supervisor.rpcinterface.SupervisorNamespaceRPCInterface.signalProcess
     * @param string $name Name of the process to signal (or ‘group:name’)
     * @param string|int $signal Signal to send, as name (‘HUP’) or number (‘1’)
     * @return boolean
     * @throws ApiException
     */
    public function signalProcess(string $name, $signal) {
        return $this->request('supervisor.signalProcess', [$name, $signal]);
    }

    /**
     * Send a signal to all processes in the process list
     *
     * @see http://supervisord.org/api.html#supervisor.rpcinterface.SupervisorNamespaceRPCInterface.signalAllProcesses
     * @param string|int $signal Signal to send, as name (‘HUP’) or number (‘1’)
     * @return array An array of process status info structs
     * @throws ApiException
     */
    public function signalAllProcesses($signal) {
        return $this->request('supervisor.signalAllProcesses', [$signal]);
    }

    /**
     * Send a signal to all processes in the group named ‘name’
     *
     * @see http://supervisord.org/api.html#supervisor.rpcinterface.SupervisorNamespaceRPCInterface.signalProcessGroup
     * @param string $name The group name
     * @param string|int $signal Signal to send, as name (‘HUP’) or number (‘1’)
     * @return array
     * @throws ApiException
     */
    public function signalProcessGroup(string $name, $signal) {
        return $this->request('supervisor.signalProcessGroup', [$name, $signal]);
    }

    /**
     * Send a string of chars to the stdin of the process name. If non-7-bit data is sent (unicode),
     * it is encoded to utf-8 before being sent to the process’ stdin. If chars is not a string or
     * is not unicode, raise INCORRECT_PARAMETERS. If the process is not running, raise NOT_RUNNING.
     * If the process’ stdin cannot accept input (e.g. it was closed by the child process), raise NO_FILE.
     *
     * @see http://supervisord.org/api.html#supervisor.rpcinterface.SupervisorNamespaceRPCInterface.sendProcessStdin
     * @param string $name The process name to send to (or ‘group:name’)
     * @param string $chars The character data to send to the process
     * @return boolean result Always return True unless error
     * @throws ApiException
     */
    public function sendProcessStdin(string $name, string $chars) {
        return $this->request('supervisor.sendProcessStdin', [$name, $chars]);
    }

    /**
     * Send an event that will be received by event listener subprocesses subscribing to the RemoteCommunicationEvent.
     *
     * @see http://supervisord.org/api.html#supervisor.rpcinterface.SupervisorNamespaceRPCInterface.sendRemoteCommEvent
     * @param string $type String for the “type” key in the event header
     * @param string $data Data for the event body
     * @return boolean Always return True unless error
     * @throws ApiException
     */
    public function sendRemoteCommEvent(string $type, string $data) {
        return $this->request('supervisor.sendRemoteCommEvent', [$type, $data]);
    }

    /**
     * Reload the configuration.
     * The result contains three arrays containing names of process groups:
     *  - added gives the process groups that have been added
     *  - changed gives the process groups whose contents have changed
     *  - removed gives the process groups that are no longer in the configuration
     *
     * @see http://supervisord.org/api.html#supervisor.rpcinterface.SupervisorNamespaceRPCInterface.reloadConfig
     * @return array result [[added, changed, removed]]
     * @throws ApiException
     */
    public function reloadConfig() {
        return $this->request('supervisor.reloadConfig');
    }

    /**
     * Read length bytes from name’s stdout log starting at offset
     *
     * @see http://supervisord.org/api.html#supervisor.rpcinterface.SupervisorNamespaceRPCInterface.readProcessStdoutLog
     * @param string $name the name of the process (or ‘group:name’)
     * @param int $offset offset to start reading from
     * @param int $length number of bytes to read from the log
     * @return string result Bytes of log
     * @throws ApiException
     */
    public function readProcessStdoutLog(string $name, int $offset, int $length = 1024) {
        return $this->request('supervisor.readProcessStdoutLog', [$name, $offset, $length]);
    }

    /**
     * Read length bytes from name’s stdout log starting at offset
     *
     * @see http://supervisord.org/api.html#supervisor.rpcinterface.SupervisorNamespaceRPCInterface.readProcessStderrLog
     * @param string $name the name of the process (or ‘group:name’)
     * @param int $offset offset to start reading from
     * @param int $length number of bytes to read from the log
     * @return string result Bytes of log
     * @throws ApiException
     */
    public function readProcessStderrLog(string $name, int $offset, int $length = 1024) {
        return $this->request('supervisor.readProcessStderrLog', [$name, $offset, $length]);
    }

    /**
     * Provides a more efficient way to tail the (stdout) log than readProcessStdoutLog(). Use readProcessStdoutLog()
     * to read chunks and tailProcessStdoutLog() to tail.
     *
     * Requests (length) bytes from the (name)’s log, starting at (offset). If the total log size is greater
     * than (offset + length), the overflow flag is set and the (offset) is automatically increased to position
     * the buffer at the end of the log. If less than (length) bytes are available, the maximum number of available
     * bytes will be returned. (offset) returned is always the last offset in the log +1.
     *
     * @see http://supervisord.org/api.html#supervisor.rpcinterface.SupervisorNamespaceRPCInterface.tailProcessStdoutLog
     * @param string $name the name of the process (or ‘group:name’)
     * @param int $offset offset to start reading from
     * @param int $length number of bytes to read from the log
     * @return array result [string bytes, int offset, bool overflow]
     * @throws ApiException
     */
    public function tailProcessStdoutLog(string $name, int $offset, int $length = 1024) {
        return $this->request('supervisor.tailProcessStdoutLog', [$name, $offset, $length]);
    }

    /**
     * Provides a more efficient way to tail the (stderr) log than readProcessStderrLog(). Use readProcessStderrLog()
     * to read chunks and tailProcessStderrLog() to tail.
     *
     * Requests (length) bytes from the (name)’s log, starting at (offset). If the total log size is greater
     * than (offset + length), the overflow flag is set and the (offset) is automatically increased to position
     * the buffer at the end of the log. If less than (length) bytes are available, the maximum number of available
     * bytes will be returned. (offset) returned is always the last offset in the log +1.
     *
     * @see http://supervisord.org/api.html#supervisor.rpcinterface.SupervisorNamespaceRPCInterface.tailProcessStderrLog
     * @param string $name the name of the process (or ‘group:name’)
     * @param int $offset offset to start reading from
     * @param int $length number of bytes to read from the log
     * @return array result [string bytes, int offset, bool overflow]
     * @throws ApiException
     */
    public function tailProcessStderrLog(string $name, int $offset, int $length = 1024) {
        return $this->request('supervisor.tailProcessStderrLog', [$name, $offset, $length]);
    }

    /**
     * Clear the stdout and stderr logs for the named process and reopen them.
     *
     * @see http://supervisord.org/api.html#supervisor.rpcinterface.SupervisorNamespaceRPCInterface.clearProcessLogs
     * @param string $name The name of the process (or ‘group:name’)
     * @return boolean result Always True unless error
     * @throws ApiException
     */
    public function clearProcessLogs(string $name) {
        return $this->request('supervisor.clearProcessLogs', [$name]);
    }

    /**
     * Clear all process log files
     *
     * @see http://supervisord.org/api.html#supervisor.rpcinterface.SupervisorNamespaceRPCInterface.clearProcessLogs
     * @return array result An array of process status info structs
     * @throws ApiException
     */
    public function clearAllProcessLogs() {
        return $this->request('supervisor.clearAllProcessLogs');
    }
}

