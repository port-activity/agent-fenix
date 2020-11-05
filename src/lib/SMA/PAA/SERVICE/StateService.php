<?php
namespace SMA\PAA\SERVICE;

use SMA\PAA\Session;

class StateService implements IStateService
{
    public function get(string $key)
    {
        $client = new RedisClient();
        $data = $client->get($key);
        if ($data) {
            return unserialize($data);
        }
        return null;
    }
    public function set(string $key, $data)
    {
        $client = new RedisClient();
        return $client->set($key, serialize($data));
    }
    public function getSet(string $key, callable $callback, int $expires = null)
    {
        $client = new RedisClient();
        $data = $client->get($key);
        if ($data) {
            $data = unserialize($data);
        } else {
            $data = call_user_func($callback);
            $client->set($key, serialize($data));
        }
        if ($expires) {
            $client->expire($key, $expires);
        }
        return $data;
    }
    public function delete(string $key)
    {
        $client = new RedisClient();
        $client->del($key);
    }
    private function rebuildActivePortCallList()
    {
        $service = new PortCallService();
        $this->set(self::LATEST_PORT_CALL_IMOS, $service->ongoingPortCallImos());
    }
    public function triggerPortCalls()
    {
        //TODO: we should build separated worker for this
        $this->delete(self::LATEST_PORT_CALLS);
        $this->rebuildActivePortCallList();
        $sse = new SseService();
        $sse->trigger("portcalls", "changed", []);
    }
    public function triggerLogistics()
    {
        $this->delete(self::LATEST_LOGISTICS);
        $sse = new SseService();
        $sse->trigger("logistics", "changed", []);
    }
    public function triggerPinnedVessels()
    {
        $session = new Session();
        $user = $session->user();
        if ($user) {
            $this->delete(StateService::PINNED_VESSELS . "." . $user->id);
            $sse = new SseService();
            $sse->trigger("portcalls", "changed-" . $user->id, []);
        }
    }
}
