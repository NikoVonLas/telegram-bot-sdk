<?php

namespace Telegram\Bot\Traits;

use Telegram\Bot\Objects\Update;
use Telegram\Bot\Commands\CommandBus;

/**
 * CommandsHandler.
 */
trait CommandsHandler
{
    /**
     * Return Command Bus.
     *
     * @return CommandBus
     */
    protected function getCommandBus(): CommandBus
    {
        return CommandBus::Instance()->setTelegram($this);
    }

    /**
     * Get all registered commands.
     *
     * @return array
     */
    public function getCommands(): array
    {
        return $this->getCommandBus()->getCommands();
    }

    /**
     * Processes Inbound Commands.
     *
     * @param bool $webhook
     *
     * @return array[]|string[]
     */
    public function commandsHandler(bool $webhook = false): array
    {
        return $webhook ? $this->useWebHook() : $this->useGetUpdates();
    }

    /**
     * Process the update object for a command from your webhook.
     *
     * @return string[]
     */
    protected function useWebHook(): array
    {
        $update = $this->getWebhookUpdate();
        return $this->processCommand($update);
    }

    /**
     * Process the update object for a command using the getUpdates method.
     *
     * @return array
     */
    protected function useGetUpdates(): array
    {
        $updates = $this->getUpdates();
        $highestId = -1;

		$updatesArr = [];
        foreach ($updates as $update) {
            $highestId = $update->updateId;
            $updatesArr[] = [
				'update_id'	=> $highestId,
				'commands' 	=> $this->processCommand($update)
			];
        }

        //An update is considered confirmed as soon as getUpdates is called with an offset higher than it's update_id.
        if ($highestId != -1) {
            $this->markUpdateAsRead($highestId);
        }

        return $updatesArr;
    }

    /**
     * Mark updates as read.
     *
     * @param $highestId
     *
     * @return Update[]
     */
    protected function markUpdateAsRead($highestId): array
    {
        $params = [];
        $params['offset'] = $highestId + 1;
        $params['limit'] = 1;

        return $this->getUpdates($params, false);
    }

    /**
     * Check update object for a command and process.
     *
     * @param Update $update
	 *
	 * @return string[]
     */
    public function processCommand(Update $update): array
    {
        return $this->getCommandBus()->handler($update);
    }

    /**
     * Helper to Trigger Commands.
     *
     * @param string $name   Command Name
     * @param Update $update Update Object
     * @param null   $entity
     *
     * @return mixed
     */
    public function triggerCommand(string $name, Update $update, $entity = null)
    {
        $entity = $entity ?? ['offset' => 0, 'length' => strlen($name) + 1, 'type' => "bot_command"];

        return $this->getCommandBus()->execute(
            $name,
            $update,
            $entity
        );
    }
}
