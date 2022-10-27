<?php
/**
 * Utility script to write the README.md to the XML for the store
 */

$readme = file_get_contents(__DIR__ . '/README.md');
$doc = new DOMDocument();
$doc->load(__DIR__ . '/appinfo/info.xml');
$xpath = new DOMXPath($doc);
/** @var DOMElement $node */
$node = $xpath->evaluate('/info/description')[0];
$node->textContent = $readme;

file_put_contents(__DIR__ . '/appinfo/info.xml', $doc->saveXML());
