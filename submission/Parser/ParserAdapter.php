<?php

namespace Submission\Parser;

use Submission\DropRepository;
use Submission\DropTemplateRepository;
use Submission\EventNodeDropRepository;

class ParserAdapter
{
    /**
     * @var DropRepository
     */
    private $dropRepository;
    /**
     * @var DropTemplateRepository
     */
    private $dropTemplateRepository;
    /**
     * @var EventNodeDropRepository
     */
    private $eventNodeDropRepository;

    public function __construct(DropRepository $dropRepository,
                                DropTemplateRepository $dropTemplateRepository,
                                EventNodeDropRepository $eventNodeDropRepository)
    {
        $this->dropRepository = $dropRepository;
        $this->dropTemplateRepository = $dropTemplateRepository;
        $this->eventNodeDropRepository = $eventNodeDropRepository;
    }

    public function generateSettings(string $eventUid, string $eventNodeUid): array
    {
        $settings = [];

        $dropTemplates = $this->makeDropTemplatesForNode($eventUid, $eventNodeUid);
        foreach ($dropTemplates as $dropTemplate) {
            $id = $this->makeTemplateName($dropTemplate) . ".png";
            $drop = $this->dropRepository->getDrop($dropTemplate["drop_uid"]);
            $isCurrency = in_array($drop["type"], ["Currency", "QP"]);

            // We are going to pass the type value according to what the parser is expecting.
            if ($isCurrency && $dropTemplate["quantity"] === null) {
                // In this scenario, the template is for a currency and drop template doesn't have a specific quantity
                // set. We want the parser to attempt to parse the quantity values
                $type = "currency";
            } else {
                // In this scenario, the template isn't a currency or contains the currency line and we don't want the
                // parser to attempt to parse the currency line. Passing it as a material type avoids that.
                $type = "material";
            }

            $settings[] = [
                "id" => $id,
                "type" => $type
            ];
        }

        return $settings;
    }

    public function generateTemplateFile($dropTemplate): array
    {
        $filename = $this->makeTemplateName($dropTemplate) . ".png";
        $contents = $this->dropTemplateRepository->getDropTemplateImage($dropTemplate["id"]);

        return compact("filename", "contents");
    }

    public function makeDropTemplatesForNode(string $eventUid, string $eventNodeUid): array
    {
        $dropTemplates = [];

        $eventNodeDrops = $this->eventNodeDropRepository->getDrops($eventUid, $eventNodeUid);
        foreach ($eventNodeDrops as $eventNodeDrop) {
            $dropUid = $eventNodeDrop["uid"];
            $quantity = intval($eventNodeDrop["quantity"]);

            // If eventNodeDrop is mapped to a invalid drop, do not add
            $drop = $this->dropRepository->getDrop($dropUid);
            if (!$drop) {
                continue;
            }

            // Check if parent templates was used. If it does, skip because we don't need specific templates
            if ($this->dropTemplatesHasParentTemplate($dropTemplates, $dropUid)) {
                continue;
            }

            // Fetch specific drop templates. If it finds them, add them to the list, then skip rest
            $dropTemplateWithBonuses = $this->dropTemplateRepository->getDropTemplates($dropUid, $quantity);
            if (count($dropTemplateWithBonuses)) {
                $dropTemplates = array_merge($dropTemplates, $dropTemplateWithBonuses);
                continue;
            }

            // At this point, if it couldn't find a specific template, try to fetch a parent template
            $parentDropTemplates = $this->dropTemplateRepository->getDropTemplates($dropUid, null);
            if (count($parentDropTemplates)) {
                // Remove all templates matching drop
                $dropTemplates = array_filter($dropTemplates, function ($dropTemplate) use ($dropUid) {
                    return $dropTemplate["drop_uid"] !== $dropUid;
                });
                // Then add the parent templates to the list
                $dropTemplates = array_merge($dropTemplates, $parentDropTemplates);
                continue;
            }

            // At this point, no matching templates could be found
        }

        return $dropTemplates;
    }

    private function dropTemplatesHasParentTemplate(array $dropTemplates, string $dropUid): bool
    {
        foreach ($dropTemplates as $dropTemplate) {
            if ($dropTemplate["drop_uid"] === $dropUid && $dropTemplate["quantity"] === null) {
                return true;
            }
        }

        return false;
    }

    private function makeTemplateName(array $dropTemplate): string
    {
        $name = $dropTemplate["drop_uid"];

        if ($dropTemplate["quantity"])
            $name .= "_Q" . $dropTemplate["quantity"];

        if ($dropTemplate["bonus"])
            $name .= "_B" . $dropTemplate["bonus"];

        return $name;
    }

}
