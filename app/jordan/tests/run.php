<?php
declare(strict_types=1);

$tests = [
	__DIR__ . '/biens/BienOwnersTest.php',
	__DIR__ . '/proprietaires/ProprietaireAssociationTest.php',
];

foreach ($tests as $test) {
	require $test;
}

echo "Tous les tests immobiliers sont OK.\n";
