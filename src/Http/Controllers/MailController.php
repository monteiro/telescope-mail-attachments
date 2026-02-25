<?php

namespace Monteiro\TelescopeMailAttachments\Http\Controllers;

use Laravel\Telescope\Http\Controllers\MailController as TelescopeMailController;
use Monteiro\TelescopeMailAttachments\MailAttachmentWatcher;

class MailController extends TelescopeMailController
{
    /**
     * The watcher class for the controller.
     *
     * @return string
     */
    protected function watcher()
    {
        return MailAttachmentWatcher::class;
    }
}
