<?php

namespace TMV\PsalmFantasyLand;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use SimpleXMLElement;
use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;

class Plugin implements PluginEntryPointInterface
{
    public function __invoke(RegistrationInterface $registration, ?SimpleXMLElement $config = null): void
    {
        foreach ($this->getStubFiles() as $file) {
            $registration->addStubFile($file);
        }

        $classes = [
            Hooks\CurryNReturnTypeProvider::class,
            Hooks\ApplicativeReturnTypeProvider::class,
        ];

        foreach ($classes as $class) {
            class_exists($class);
            $registration->registerHooksFromClass($class);
        }
    }

    /** @return array<string> */
    private function getStubFiles(): array
    {
        return $this->rsearch(__DIR__ . '/../stubs/', '/^.*\.phpstub$/');
    }

    /** @return array<string> */
    private function rsearch(string $folder, string $pattern): array
    {
        $dir = new RecursiveDirectoryIterator($folder);
        $ite = new RecursiveIteratorIterator($dir);
        $files = new RegexIterator($ite, $pattern, RegexIterator::GET_MATCH);

        return array_merge([], ...array_values(iterator_to_array($files)));
    }
}
