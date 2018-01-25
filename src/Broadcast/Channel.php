<?php

namespace GigaAI\Broadcast;

class Channel
{
    /**
     * Create new Facebook Label
     *
     * @param String $name
     * @return Label ID
     */
    public function create($name)
    {
        $response = giga_facebook_post('me/custom_labels', compact('name'));

        if (isset($response->error)) {
            $message = isset($response->error->error_user_msg) ? $response->error->error_user_msg : $response->message;
            throw new \Exception($message);
        }

        if (isset($response->id)) {
            return $response->id;
        }
    }

    /**
     * Add lead to Facebook label
     *
     * @return bool
     */
    public function addLead($labelId, $leadId)
    {
        $response = giga_facebook_post($labelId . '/label', [
            'user' => $leadId
        ]);

        return isset($response->success);
    }

    /**
     * Add many leads to Facebook Label
     */
    public function addLeads($labelId, array $leadIds)
    {
        $batch = [];

        foreach ($leadIds as $user) {
            $batch[] = [
                'method' => 'POST',
                'relative_url' => $labelId . '/label',
                'body' => http_build_query(compact('user'))
            ];
        }

        $batch = json_encode($batch);
        
        return giga_facebook_post('', compact('batch'));
    }

    /**
     * Remove lead from FB label
     *
     * @return bool
     */
    public function removeLead($labelId, $leadId)
    {
        $response = giga_facebook_delete($labelId . '/label', [
            'user' => $leadId
        ]);

        return isset($response->success);
    }

    public function removeLeads($labelId, array $leadIds)
    {
        $batch = [];

        foreach ($leadIds as $user) {
            $batch[] = [
                'method' => 'DELETE',
                'relative_url' => $labelId . '/label?user=' . $user
            ];
        }

        $batch = json_encode($batch);
        
        return giga_facebook_post('', compact('batch'));
    }

    /**
     * Get all labels of lead
     *
     * @return mixed
     */
    public function ofLead($leadId)
    {
        $response = giga_facebook_get($leadId . '/custom_labels');

        if (!isset($response->data) || !is_array($response->data)) {
            return false;
        }

        $channels = $response->data;

        $channelIds = [];
        foreach ($channels as $channel) {
            $channelIds[] = $channel->id;
        }

        return $channelIds;
    }

    /**
     * Get label detail
     *
     * @return array
     */
    public function detail($labelId)
    {
        $response = giga_facebook_get($labelId . '?fields=name');

        return $response; // [name => , id =>]
    }

    /**
     * Get all label of a page
     *
     * @return array
     */
    public function all()
    {
        $response = giga_facebook_get('/me/custom_labels?fields=name');

        $channels = [];
        foreach ($response->data as $channel) {
            $channels[$channel->id] = $channel->name;
        }

        return $channels; // [id => name]
    }

    /**
     * Delete label
     *
     * @return bool
     */
    public function delete($labelId)
    {
        $response = giga_facebook_delete($labelId);

        return isset($response->success);
    }

    public function empty()
    {
        $channels = $this->all();

        foreach ($channels as $id => $name) {
            $success = $this->delete($id);
        }

        return $success;
    }
}
