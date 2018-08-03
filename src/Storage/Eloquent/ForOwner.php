<?php

namespace GigaAI\Storage\Eloquent;

trait ForOwner
{
    public function scopeForOwner($query, $ownerId = null, $type = null)
    {
        return is_inside_wp() ? $this->forWPOwner($query, $ownerId, $type) : $this->forLaravelOwner($query, $ownerId, $type);
    }

    private function forWPOwner($query, $ownerId = null, $type = null)
    {
        $user = wp_get_current_user();

        return $user->ID === $ownerId;
    }

    private function forLaravelOwner($query, $ownerId = null, $type = null)
    {
        $user = auth()->user();
        
        if ($user->can('view', $this)) {
            if ($ownerId !== null) {
                return $query->whereCreatorId($ownerId);
            }

            return $query;
        }

        if ($type !== null) {
            $this->type = $type;
        }

        if ($user->can('view_own', $this)) {
            if ($this instanceof Lead) {
                $sources = $user->instances()->pluck('page_id')->toArray();

                return $query->whereCreatorId($user->id)->orWhereIn('source', $sources);
            }
        
            return $query->whereCreatorId($user->id);
        }

        return $query->whereRaw('1=0');
    }
}
