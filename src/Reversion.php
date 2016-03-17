<?php

namespace Bolt\Extension\Cainc\ContentRevert;

use Bolt\Logger\ChangeLog;
use Bolt\Storage;

class Reversion
{
    /**
     * @var ChangeLog
     */
    private $changelog;

    /**
     * @var Storage
     */
    private $storage;

    /**
     * @param ChangeLog $changelog
     * @param Storage   $storage
     */
    public function __construct(ChangeLog $changelog, Storage $storage)
    {
        $this->changelog = $changelog;
        $this->storage = $storage;
    }

    /**
     * Reverts a content change from a changelog entry id
     *
     * @param string $contenttype      The content type slug
     * @param int    $contentid        The content ID
     * @param int    $id               The changelog entry ID
     * @param bool   $skipHiddenFields Whether or not we should skip hidden fields, default false
     *
     * @throws \RuntimeException
     *
     * @return mixed
     */
    public function revertChange($contenttype, $contentid, $id, $skipHiddenFields = false)
    {
        // Get the Current Entry
        $entry = $this->changelog->getChangelogEntry($contenttype, $contentid, $id);

        if (empty($entry)) {
            throw new \RuntimeException('Changelog entry must exist.');
        }

        // Get the content record
        $record = $this->storage->getContent($contenttype, [
            'id' => $entry->contentid,
            'returnsingle' => true,
        ]);

        // For each of the updated fields update our original content
        foreach ($entry->changedfields as $key => $field) {
            // Skip updating hidden fields if configured
            if ($skipHiddenFields && $this->isFieldHidden($record->contenttype['fields'][$key])) {
                continue;
            }

            $record->setValue($key, $field['before']['raw']);
        }

        // Save the content record - It will create a new revision entry in the log.
        $this->storage->saveContent($record, 'Reverted to previous version');
    }

    /**
     * Determine whether or not a field is hidden
     *
     * As the hidden field type was added later in the Bolt 2.x lifetime
     * we'll also check for an added hidden class, which is used as a
     * common workaround.
     *
     * @param array $field Field definition for the content type
     *
     * @return bool
     */
    private function isFieldHidden($field)
    {
        if (isset($field['type']) && $field['type'] === 'hidden') {
            return true;
        }

        if (isset($field['class'])) {
            $classes = explode(' ', $field['class']);

            if (in_array('hidden', $classes)) {
                return true;
            }
        }

        return false;
    }
}
