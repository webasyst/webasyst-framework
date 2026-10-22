<?php

interface siteBlockTypeOptionsInterface
{
    public function setTypeOptions(array $type_options): void;
    public function getTypeOptions(): array;
}
