<?php

class teamUsersInvitationsController extends waJsonController
{
    public function execute()
    {
        $tokens = teamUser::getInviteTokens($this->getIds());

        $invitations = [];
        foreach ($tokens as $contact_id => $token) {
            $invitations[$contact_id] = [
                'link' => waAppTokensModel::getLink($token)
            ];
        }

        $this->response = [
            'invitations' => $invitations
        ];
    }

    protected function getIds()
    {
        $ids = waUtils::toIntArray($this->getRequest()->get('id'));
        return waUtils::dropNotPositive($ids);
    }
}
