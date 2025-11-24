<?php

namespace Mwb\LaminasGenerator;

/*
composer require phpmyadmin/sql-parser "^6.0"
composer require doctrine/inflector "^2.1"
composer require twig/twig
composer require laminas/laminas-filter
*/

use Doctrine\Inflector\InflectorFactory;
use Laminas\Filter\Word\UnderscoreToCamelCase;
use Laminas\Filter\Word\CamelCaseToUnderscore;
use Laminas\Filter\Word\CamelCaseToSeparator;
use Laminas\Filter\Word\CamelCaseToDash;

use Twig\Environment;
//use Twig\Loader\ArrayLoader;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;


/* Improve rootLayers *.mwb */
class Box
{
	public $parent = Null;// Box

	public $container;

	public $left;
	public $top;
	public $width;
	public $height;
	public $right;
	public $bottom;

	public $children = [];// Of Box

	public function __construct($layers) {
		$this->container = $layers;
		$this->left = $layers->left;
		$this->top = $layers->top;
		$this->width = $layers->width;
		$this->height = $layers->height;
		$this->bottom = $layers->top + $layers->height;
		$this->right = $layers->left + $layers->width;
	}

	public function contains(Box $child) {
		$isIn = False;
		if ($this->left < $child->left) {
			if ($this->top < $child->top) {
				if ($this->bottom > $child->bottom) {
					if ($this->right > $child->right) {
						$isIn = True;
					}
				}
			}
		}
		return $isIn;
	}

	static public function sort(ArrayObject $layers) {
		$boxs = [];
		foreach($layers as $layer) {
			if (empty($layer)) continue;
			$boxs[] = new Box($layer);
		}
		// // find root
		$boxLen = count($boxs);
		for($i=0; $i<$boxLen; $i++) {
			$possibleParent = [];
			$boxI = $boxs[$i];
			$possibleParent = array_filter($boxs, fn($p) => $p!==$boxI && $p->contains($boxI));

			// reduce by area (the most close)
			if (!empty($possibleParent)) {
				$parent = array_reduce($possibleParent, function($min, $p){
					$area = $p->width * $p->height;
					if (!$min) return $p;
					$minArea = $min->width * $min->height;
					return $area < $minArea ? $p : $min;
				});
				$boxI->parent = $parent;
				$parent->children[] = $boxI;
			}
		}
		$roots = array_values(array_filter($boxs, fn($c)=>$c->parent===Null));

		//$diagram->rootLayer->subLayers
		return $roots;
	}
}

/* Naming */

interface NamingStrategy {}
class StandardNaming implements NamingStrategy
{
	public $underscoreToCamelCaseFilter;
	public $inflectorFilter;

	public function __construct() {
		$this->underscoreToCamelCaseFilter = new UnderscoreToCamelCase();
		$this->inflectorFilter = InflectorFactory::create()->build();
	}

	static protected function fileify($name_code) {
		// entityify, classify, tableClass, gatewayify
	}

	public function entityify($name_code, $pluralize=False) {
		$names = explode('_', $name_code);
		$last = array_pop($names);
		$parts = [];
		foreach($names as $name) {
			$parts[] = $this->inflectorFilter->singularize($name);
		}
		if ($pluralize) {
			$last = $this->inflectorFilter->pluralize($last);
		} else {
			$last = $this->inflectorFilter->singularize($last);
		}
		$parts[] = $last;
		$name_code = implode('_', $parts);
		return $name_code;
	}
}


/* unit CRUD
 */
class Crud {
	const CREATE = 'create';
	const READ = 'read';
	const UPDATE = 'update';
	const DELETE = 'delete';
}

class ConfigGenerator {
	public $whiteTables = [
		//'accounts', 'companies', 'companies_associates',
		//'statements',
		// 'operations',
		//'operation_descriptions',
		'city',
	];
	public $blackTables = [];

	public $crud = [
		Crud::CREATE => ['add' => ['POST', 'GET']],
		Crud::READ   => ['index'=> ['GET'],
			         'view'=> ['GET']],
		Crud::UPDATE => ['edit' => ['POST', 'GET']],
		Crud::DELETE => ['del' => ['GET', 'POST']],
	];
}

class BaseGenerator {
	protected $config;
	protected NamingStrategy $naming;

	public $enableExport = False;

	protected $twig;
	public function __construct() {
		$this->config = new ConfigGenerator();
		$this->naming = new StandardNaming();
		$naming = $this->naming;

		$loader = new FilesystemLoader(__DIR__.'/../view');
		$this->twig = new Environment($loader, [
			'cache' => False
		]);
/*
		$snake_caseFilter = new TwigFilter('snake_case', function($string) use($naming) {
			$string = icon('UTF-8', 'ASCII//TRANSLIT', $string);
			$string = preg_replace('#[^a-zA-Z0-9]+#', '_', $string);
			$string = strtolower(trim($string, '_'));
			return $string;
		});
		$slugifyFilter = new TwigFilter('slugify', function($string) use($naming) {
			$string = icon('UTF-8', 'ASCII//TRANSLIT', $string);
			$string = preg_replace('#[^a-zA-Z0-9]+#', '-', $string);
			$string = strtolower(trim($string, '-'));
			return $string;
		});
*/
		$entityfyFilter = new TwigFilter('entityify', function($string) use($naming) {
			$string = $naming->entityify($string, False);
			
			$filter = new UnderscoreToCamelCase($string); // => 'operationDescription'
			$string = $filter->filter($string);
			return $string;
		});
		$variabilizeFilter = new TwigFilter('variabilize', function($string, $pluralize=False, $preffix='$') use($naming) {
			$string = $naming->entityify($string, $pluralize);
			
			$filter = new UnderscoreToCamelCase($string); // => 'operationDescription'
			$string = $filter->filter($string);
			$string = lcfirst($string);
			return $preffix.$string;
		});
		$slugifyFilter = new TwigFilter('slugify', function($string, $pluralize=False) use($naming) {
			$string = $naming->entityify($string, $pluralize);
			
			$filter = new CamelCaseToDash($string); // => 'operationDescription'
			$string = $filter->filter($string);
			$string = strtolower($string);
			return $string;
		});
		$verbalizeFilter = new TwigFilter('verbalize', function($string, $pluralize=False, $useLast=False) use($naming) {
			$string = $naming->entityify($string, $pluralize);
			
			$string = strtolower($string);
			$names = explode('_', $string);
			if ($pluralize && count($names)>1) {
				$first = $names[0];
				$names[0] = $first . '\'s';
			}
			$string = implode(' ', $names);
			if ($useLast) {
				$string = $names[count($names)-1];
			}
			$string = ucfirst($string);
			return $string;
		});

		$formElementTypeFilter = new TwigFilter('formElementTypefy', function($dbColumn) use($naming) {
			$simpleTypeElements = [
				'com.mysql.rdbms.mysql.datatype.tinyint'            => 'Text::class',
				'com.mysql.rdbms.mysql.datatype.smallint'           => 'Text::class',
				'com.mysql.rdbms.mysql.datatype.mediumint'          => 'Text::class',
				'com.mysql.rdbms.mysql.datatype.int'                => 'Text::class',
				'com.mysql.rdbms.mysql.datatype.bigint'             => 'Text::class',
				'com.mysql.rdbms.mysql.datatype.float'              => 'Text::class',
				'com.mysql.rdbms.mysql.datatype.real'               => 'Text::class',
				'com.mysql.rdbms.mysql.datatype.double'             => 'Text::class',
				'com.mysql.rdbms.mysql.datatype.decimal'            => 'Text::class',
				'com.mysql.rdbms.mysql.datatype.char'               => 'Text::class',
				'com.mysql.rdbms.mysql.datatype.nchar'              => 'Text::class',
				'com.mysql.rdbms.mysql.datatype.varchar'            => 'Text::class',
				'com.mysql.rdbms.mysql.datatype.nvarchar'           => 'Text::class',
				'com.mysql.rdbms.mysql.datatype.binary'             =>               'Text::class',// ?
				'com.mysql.rdbms.mysql.datatype.varbinary'          =>               'Text::class',// ?
				'com.mysql.rdbms.mysql.datatype.tinytext'           => 'Text::class',
				'com.mysql.rdbms.mysql.datatype.text'               => 'Text::class',
				'com.mysql.rdbms.mysql.datatype.mediumtext'         => 'Text::class',
				'com.mysql.rdbms.mysql.datatype.longtext'           => 'Text::class',
				'com.mysql.rdbms.mysql.datatype.tinyblob'           =>               'Text::class',// ?
				'com.mysql.rdbms.mysql.datatype.blob'               =>               'Text::class',// ?
				'com.mysql.rdbms.mysql.datatype.mediumblob'         =>               'Text::class',// ?
				'com.mysql.rdbms.mysql.datatype.longblob'           =>               'Text::class',// ?
				'com.mysql.rdbms.mysql.datatype.json'               => 'Text::class',
				'com.mysql.rdbms.mysql.datatype.datetime'           =>               'Text::class',// ?
				'com.mysql.rdbms.mysql.datatype.datetime_f'         =>               'Text::class',// ?
				'com.mysql.rdbms.mysql.datatype.date'               =>               'Text::class',// ?
				'com.mysql.rdbms.mysql.datatype.time'               =>               'Text::class',// ?
				'com.mysql.rdbms.mysql.datatype.time_f'             =>               'Text::class',// ?
				'com.mysql.rdbms.mysql.datatype.year'               =>               'Text::class',// ?
				'com.mysql.rdbms.mysql.datatype.timestamp'          =>               'Text::class',// ?
				'com.mysql.rdbms.mysql.datatype.timestamp_f'        =>               'Text::class',// ?
				'com.mysql.rdbms.mysql.datatype.geometry'           =>               'Text::class',// ?
				'com.mysql.rdbms.mysql.datatype.point'              =>               'Text::class',// ?
				'com.mysql.rdbms.mysql.datatype.linestring'         =>               'Text::class',// ?
				'com.mysql.rdbms.mysql.datatype.polygon'            =>               'Text::class',// ?
				'com.mysql.rdbms.mysql.datatype.geometrycollection' =>               'Text::class',// ?
				'com.mysql.rdbms.mysql.datatype.multipoint'         =>               'Text::class',// ?
				'com.mysql.rdbms.mysql.datatype.multilinestring'    =>               'Text::class',// ?
				'com.mysql.rdbms.mysql.datatype.multipolygon'       =>               'Text::class',// ?
				'com.mysql.rdbms.mysql.datatype.bit'                =>               'Text::class',// ?
				'com.mysql.rdbms.mysql.datatype.enum'               => 'Select::class',
				'com.mysql.rdbms.mysql.datatype.set'                =>               'Text::class',// ?
			];

			$userTypeElements = [
				'MY_DEVISE'                => 'Text::class',
				//'MY_DEVISE'                => '\\Application\\Filter\\ToDevise',
			];

			
			if (isset($dbColumn->userType)) {
				if (array_key_exists($dbColumn->userType->name, $userTypeElements)) {
					$elementName = $userTypeElements[$dbColumn->userType->name];
				} else {
					$elementName = $simpleTypeElements[$dbColumn->userType->actualType->name];
				}
			} elseif (array_key_exists($dbColumn->simpleType->name, $simpleTypeElements)) {
				$elementName = $simpleTypeElements[$dbColumn->simpleType->name];
			} else {
				echo 'Warning : simpleElement "'.$dbColumn->simpleType->name . '" not found' .PHP_EOL;
			}

			return $elementName;
		});


		$dbColumnDeclareFilter = new TwigFilter('dataDeclare', function($dbColumn) use($naming) {
			$simpleTypes = [
				'com.mysql.rdbms.mysql.datatype.tinyint'            => 'int',
				'com.mysql.rdbms.mysql.datatype.smallint'           => 'int',
				'com.mysql.rdbms.mysql.datatype.mediumint'          => 'int',
				'com.mysql.rdbms.mysql.datatype.int'                => 'int',
				'com.mysql.rdbms.mysql.datatype.bigint'             => 'int',
				'com.mysql.rdbms.mysql.datatype.float'              => 'float',
				'com.mysql.rdbms.mysql.datatype.real'               => 'float',
				'com.mysql.rdbms.mysql.datatype.double'             => 'float',
				'com.mysql.rdbms.mysql.datatype.decimal'            => 'float',
				'com.mysql.rdbms.mysql.datatype.char'               => 'string',
				'com.mysql.rdbms.mysql.datatype.nchar'              => 'string',
				'com.mysql.rdbms.mysql.datatype.varchar'            => 'string',
				'com.mysql.rdbms.mysql.datatype.nvarchar'           => 'string',
				'com.mysql.rdbms.mysql.datatype.binary'             => 'mixed',
				'com.mysql.rdbms.mysql.datatype.varbinary'          => 'mixed',
				'com.mysql.rdbms.mysql.datatype.tinytext'           => 'string',
				'com.mysql.rdbms.mysql.datatype.text'               => 'string',
				'com.mysql.rdbms.mysql.datatype.mediumtext'         => 'string',
				'com.mysql.rdbms.mysql.datatype.longtext'           => 'string',
				'com.mysql.rdbms.mysql.datatype.tinyblob'           => 'mixed',
				'com.mysql.rdbms.mysql.datatype.blob'               => 'mixed',
				'com.mysql.rdbms.mysql.datatype.mediumblob'         => 'mixed',
				'com.mysql.rdbms.mysql.datatype.longblob'           => 'mixed',
				'com.mysql.rdbms.mysql.datatype.json'               => 'string',
				'com.mysql.rdbms.mysql.datatype.datetime'           => 'mixed',
				'com.mysql.rdbms.mysql.datatype.datetime_f'         => 'mixed',
				'com.mysql.rdbms.mysql.datatype.date'               => 'mixed',
				'com.mysql.rdbms.mysql.datatype.time'               => 'mixed',
				'com.mysql.rdbms.mysql.datatype.time_f'             => 'mixed',
				'com.mysql.rdbms.mysql.datatype.year'               => 'mixed',
				'com.mysql.rdbms.mysql.datatype.timestamp'          => 'mixed',
				'com.mysql.rdbms.mysql.datatype.timestamp_f'        => 'mixed',
				'com.mysql.rdbms.mysql.datatype.geometry'           => 'mixed',
				'com.mysql.rdbms.mysql.datatype.point'              => 'mixed',
				'com.mysql.rdbms.mysql.datatype.linestring'         => 'mixed',
				'com.mysql.rdbms.mysql.datatype.polygon'            => 'mixed',
				'com.mysql.rdbms.mysql.datatype.geometrycollection' => 'mixed',
				'com.mysql.rdbms.mysql.datatype.multipoint'         => 'mixed',
				'com.mysql.rdbms.mysql.datatype.multilinestring'    => 'mixed',
				'com.mysql.rdbms.mysql.datatype.multipolygon'       => 'mixed',
				'com.mysql.rdbms.mysql.datatype.bit'                => 'mixed',
				'com.mysql.rdbms.mysql.datatype.enum'               => 'mixed',// enum
				'com.mysql.rdbms.mysql.datatype.set'                => 'mixed',
			];

			$propertyDecl = '';
			if (isset($dbColumn->userType)) {
				$type = $simpleTypes[$dbColumn->userType->actualType->name];
				//$commentType = '// '.$type.' => ' . $dbColumn->userType->name . '<'.$dbColumn->userType->sqlDefinition.'>('.$dbColumn->userType->flags.') so use specifique Filter';
			} else if ( array_key_exists($dbColumn->simpleType->name, $simpleTypes) ) {
				$type = $simpleTypes[$dbColumn->simpleType->name];
				//$commentType = '// ' . $type;
			} else {
				echo 'Warning : simpleType "'.$dbColumn->simpleType->name . '" not found' .PHP_EOL;
			}


			$propertyDecl = 'public ?'.$type.' $'.$dbColumn->name.' = Null';
			return $propertyDecl;
		});

		$dbColumnAsFilter = new TwigFilter('columnsAsFilter', function($dbColumns) use($naming) {
			// Default : Injection protect
			$simpleTypeFilters = [
				'com.mysql.rdbms.mysql.datatype.tinyint'            => 'ToInt::class',
				'com.mysql.rdbms.mysql.datatype.smallint'           => 'ToInt::class',
				'com.mysql.rdbms.mysql.datatype.mediumint'          => 'ToInt::class',
				'com.mysql.rdbms.mysql.datatype.int'                => 'ToInt::class',
				'com.mysql.rdbms.mysql.datatype.bigint'             => 'ToInt::class',
				'com.mysql.rdbms.mysql.datatype.float'              => 'ToFloat::class',
				'com.mysql.rdbms.mysql.datatype.real'               => 'ToFloat::class',
				'com.mysql.rdbms.mysql.datatype.double'             => 'ToFloat::class',
				'com.mysql.rdbms.mysql.datatype.decimal'            => 'ToFloat::class',
				'com.mysql.rdbms.mysql.datatype.char'               => 'ToString::class',
				'com.mysql.rdbms.mysql.datatype.nchar'              => 'ToString::class',
				'com.mysql.rdbms.mysql.datatype.varchar'            => 'ToString::class',
				'com.mysql.rdbms.mysql.datatype.nvarchar'           => 'ToString::class',
				'com.mysql.rdbms.mysql.datatype.binary'             => Null,
				'com.mysql.rdbms.mysql.datatype.varbinary'          => Null,
				'com.mysql.rdbms.mysql.datatype.tinytext'           => 'ToString::class',
				'com.mysql.rdbms.mysql.datatype.text'               => 'ToString::class',
				'com.mysql.rdbms.mysql.datatype.mediumtext'         => 'ToString::class',
				'com.mysql.rdbms.mysql.datatype.longtext'           => 'ToString::class',
				'com.mysql.rdbms.mysql.datatype.tinyblob'           => Null,
				'com.mysql.rdbms.mysql.datatype.blob'               => Null,
				'com.mysql.rdbms.mysql.datatype.mediumblob'         => Null,
				'com.mysql.rdbms.mysql.datatype.longblob'           => Null,
				'com.mysql.rdbms.mysql.datatype.json'               => 'ToString::class',
				'com.mysql.rdbms.mysql.datatype.datetime'           => Null,
				'com.mysql.rdbms.mysql.datatype.datetime_f'         => Null,
				'com.mysql.rdbms.mysql.datatype.date'               => Null,
				'com.mysql.rdbms.mysql.datatype.time'               => Null,
				'com.mysql.rdbms.mysql.datatype.time_f'             => Null,
				'com.mysql.rdbms.mysql.datatype.year'               => Null,
				'com.mysql.rdbms.mysql.datatype.timestamp'          => Null,
				'com.mysql.rdbms.mysql.datatype.timestamp_f'        => Null,
				'com.mysql.rdbms.mysql.datatype.geometry'           => Null,
				'com.mysql.rdbms.mysql.datatype.point'              => Null,
				'com.mysql.rdbms.mysql.datatype.linestring'         => Null,
				'com.mysql.rdbms.mysql.datatype.polygon'            => Null,
				'com.mysql.rdbms.mysql.datatype.geometrycollection' => Null,
				'com.mysql.rdbms.mysql.datatype.multipoint'         => Null,
				'com.mysql.rdbms.mysql.datatype.multilinestring'    => Null,
				'com.mysql.rdbms.mysql.datatype.multipolygon'       => Null,
				'com.mysql.rdbms.mysql.datatype.bit'                => Null,
				'com.mysql.rdbms.mysql.datatype.enum'               => Null,// enum
				'com.mysql.rdbms.mysql.datatype.set'                => Null,
			];

			// Domain : use FilterChain
			$userTypeFilters = [
				'MY_DEVISE'                => '\\Application\\Filter\\ToDevise',

			];


			$columnsFilters = [];
			foreach ($dbColumns as $dbColumn) {
				$columnsFilters[$dbColumn->name] = [];
				$filterName = Null;
				$commentType = '';
				if (isset($dbColumn->userType)) {
					if ( array_key_exists($dbColumn->userType->name, $userTypeFilters) ) {
						$filterName = $userTypeFilters[$dbColumn->userType->name].'::class';
					} else {
						$filterName = $simpleTypeFilters[$dbColumn->userType->actualType->name];
					}
					$commentType = ' // ?';
				} else if ( array_key_exists($dbColumn->simpleType->name, $simpleTypeFilters) ) {
					$filterName = $simpleTypeFilters[$dbColumn->simpleType->name];
					$commentType = '// ' . $filterName;
				} else {
					echo 'Warning : simpleType "'.$dbColumn->simpleType->name . '" not found' .PHP_EOL;
					// 'ToNull::class'
				}
				if ($filterName) 
					$columnsFilters[$dbColumn->name][] = $filterName;

			}

			return $columnsFilters;
		});




		//$this->twig->addFilter($snake_caseFilter);

		$this->twig->addFilter($slugifyFilter);
		$this->twig->addFilter($entityfyFilter);
		$this->twig->addFilter($variabilizeFilter);
		$this->twig->addFilter($verbalizeFilter);// Humane readable, Humanize ?
		$this->twig->addFilter($formElementTypeFilter);
		$this->twig->addFilter($dbColumnDeclareFilter);
		$this->twig->addFilter($dbColumnAsFilter);

		// variabilize

		// Configure it:
		// $twig->addExtension(new CustomExtension());
		// $twig->loadExtension(new CustomExtension();
		// Inject:
		//$renderer = new TwigRenderer($twig);
	}
}

// relation: ManyToOne, OneToManu, ManyToManu, OneToOne
// owner: parent|child|orphan
// key: isole-unique|adoptable|sharable
// display: visible|hidden
// Unique, Shared, and Weak
/*

[1] Relation ownership (95% des cas)
  [A] owned : Le parent possède l'enfant
      - suppression automatique
      - cascade persist/remove
      - OneToMany pi OneToOne
      (ex: OrderItem, InvoiceLine, Photo profil)
  [B] shared : ressource partagé - Plusieurs ressource peuvent pointer vers la meme
    - ManyToMany | ManyToMany
    - pas de cascade sur remove
    - entité autonome
    (ex: Tag, adresse réutilissable, Catégorie, Produit lié a plusieur panier)
  [C] Referenced : reference qui n'implique pas de possession
    - nullable
    - aucune cascade
    - aucune suppression automatique
    - pas d'effet sur le cycle de vie
    (Dernier utilisateur qui a modifier un document, reference vers un parent optionel, auteur d'un commentaire)
[2] Agregat (DDD-light) - Permet d'exprimer qu'un groupe d'entité fonctionne comme une unité transactionnel
    tu ajoute un flag
      - aggregate_root : true/false
    - Et dans un agrega :
      - owned = entité interns
      - shared/referenced = entité externe
  (ex: Commande, factures, dossiers, comptes clients)
[3] Cardinalité (1,,optionel, requis)
   - one_to_one
   - one_to_many
   - many_to_one
   - many_to_many
      - nullable: true/false
      - unique: true/false
  (indispensable pour exprimer toutes les relation valide)

[4] Relation temporaire (rare 0.1% mais critique)
   - historique de possession
   - versionning metier
   - validité temporraire
   Representation:
   - relation : temporal
   - valid_from : date_time
   - valid_to : datetime
  ( Ex: Une addresse qui appartient a un user de 2020 à 2023
        Une equipe projet avec des membres sur differentes périodes
        Un abonnement avec une periode

[5] Relation polymorphe (rare 0.1%)
  - commentaires liés a plusieurs types d'entité
  - Fichier attaché a plusieurs type d'object

  - relation: polymorphic
  - targets
     - post
     - video
     - product


Avec ces 5 concept :
  - relation doctrine
  - gestion du cycle de vie
  - strategie de suppression
  - regle de cascade
  - contrainte SQL
  - logique metier coherente
  - API/DTO coherent


{
	relations: {
		profile: {
			type: owned,
			cardinality: one_to_many,
			target: Profil,
			orphanRemoval: true,
		}
		Addresses : {
			type: shared,
			cardinality: one_to_many,
			target: Address
		}
	}
}

// ============================================
DSL :

entities:
	EntityName:
		aggregate_root: true|false
		table : custom_table_name (optional)
		fields :
			fieldName :
				type: string|int|float|bool|datetime|text|json|uuid
				nullable: true|false
				unique: true|false
				default: value
		relations :
			relationName :
				type: owned|shared|referenced|polimorphic|temporal
				cardinality: one_to_one|one_to_many|many_to_one|many_to_many
				target: EntityName
				mappedBy: fieldName (optional)
				inversedBy: fieldName (optional)
				nullable: true|false
				orphan_removal: true|false
				cascade:
					- persist
					- remove
					- update
			temporal :
				start_field: valid_from (optional)
				end_field: valid_to (optional)
			polymorphic :
				target:
					- EntityA
					- EntityB
					- EntityC


*/

class UnitGenerator extends BaseGenerator
{
	protected $mwbOrm;
	protected $pathExport = Null;// .'../build'

	public function __construct($filepath) {
		parent::__construct();
		//$this->mwbDocument = \Mwb\Document::load($filepath);
		$this->mwbOrm = \Mwb\Orm\Loader::Load($filepath);
	}

	protected function getEntities() {
		$entities = [];

		foreach ($this->mwbOrm->getEntities() as $entity) {
			if (empty($this->config->whiteTables) || in_array($entity->dbTable->name, $this->config->whiteTables)) {
				$entities[] = $entity;
			}
		}

		return $entities;
	}

	private function findPrimaryKeys($table) {
		$primaryKeys = [];
		foreach ($table->indices as $index) {
			if ($index->isPrimary) {
				foreach ($index->columns as $indexColumn) {
					$primaryKeys[] = $indexColumn->referencedColumn->name;
				}
			}
		}
		return $primaryKeys;
	}

	private function findKeys($table) {
		$primaryKeys = $this->findPrimaryKeys($table);
		$foreignPrimaryKeys = [];
		$foreignKeys = [];
		foreach ($table->foreignKeys as $foreignKey) {
			// $foreignKey->referencedMandatory
			// $foreignKey->mandatory
			// $columns->columns
			//var_dump($foreignKey->referencedTable->name);//      string(10) "statements"
			//var_dump($foreignKey->columns[0]->name);//           string(13) "statements_id"
			//var_dump($foreignKey->referencedColumns[0]->name);//  string(2) "id"

			foreach ($foreignKey->columns as $foreignColumn) {
				if (!$foreignColumn) continue;
				if (in_array($foreignColumn->name, $primaryKeys)) {
					$foreignPrimaryKeys[] = $foreignColumn->name;
				} else {
					$foreignKeys[] = $foreignColumn->name;
				}
			}
		}
		return ['primaries'=>$primaryKeys, 'foreignPrimaries'=>$foreignPrimaryKeys, 'foreigns'=>$foreignKeys];
	}

	protected function generateController($moduleName, $entity) {
		$path = 'module/'.$moduleName.'/src/Controller';
		`mkdir -p $this->pathExport/$path`;

		$columnsPK = $entity->getPKColumns();
		$columnsFK = $entity->getFKColumns();
		$foreignPrimaries = array_intersect_key($columnsFK, $columnsPK);
		$output = $this->twig->render('src/Controller/crud.php.twig', [// $actionCode
			'crud' => $this->config->crud,
			'module' => $moduleName,
			'entity' => $entity,
		]);
		if ($this->enableExport) {
			$filename = $entity->name . 'Controller.php';
			file_put_contents($this->pathExport.'/'.$path.'/'.$filename, $output);
		} else {
			echo $output . PHP_EOL;
		}
	}

	/*
	public $crud = [
		'create' => ['add' => ['POST', 'GET']],
		'read'   => ['index'=> ['POST'],
			     'view'=> ['GET']],
		'update' => ['edit' => ['POST', 'GET']],
		'delete' => ['del' => ['GET', 'POST']],
	];
	*/
	protected function generateView($moduleName, $entity, $crudName) {
		$filter = new CamelCaseToDash();
		$EntityName = $entity->name;
		$Entity_Name = $filter->filter($EntityName);
		$entity_name = strtolower($Entity_Name);
		$path = 'module/'.$moduleName.'/view/'.$entity_name;
		`mkdir -p $this->pathExport/$path`;

		foreach ($this->config->crud[$crudName] as $actionName=>$methods) {
			//if (!in_array($crudNames, [Crud::CREATE, Crud::UPDATE])) continue;
			//if (!in_array($actionName, ['add', 'edit'])) continue;

			$output = $this->twig->render('view/application/controller/'.$crudName.'.php.twig', [// $actionName
				'module'      => $moduleName,
				'entity'      => $entity,
				'action'      => $actionName,
				//'primaryKey'        => $entity->dbColumnsPK,
				//'primaryForeignKey' => array_intersect_key($entity->dbColumnsFK, $entity->dbColumnsPK),
				//'foreignKey'        => $entity->dbColumnsFK,
			]);
			if ($this->enableExport) {
				$filename = $actionName . '.phtml';
				file_put_contents($this->pathExport.'/'.$path.'/'.$filename, $output);
			} else {
				echo $output . PHP_EOL;
			}
		}

	}

	protected function generateForm($moduleName, $entity) {
		$path = 'module/'.$moduleName.'/src/Form';
		`mkdir -p $this->pathExport/$path`;

		$output = $this->twig->render('src/Form/form.php.twig', [// $actionCode
			'module' => $moduleName,
			'entity' => $entity,
		]);
		if ($this->enableExport) {
			$filename = $entity->name . 'Form.php';
			file_put_contents($this->pathExport.'/'.$path.'/'.$filename, $output);
		} else {
			echo $output . PHP_EOL;
		}
	}

	protected function generateObject($moduleName, $entity) {
		$path = 'module/'.$moduleName.'/src/Model';
		`mkdir -p $this->pathExport/$path`;

		$output = $this->twig->render('src/Model/object.php.twig', [
			'module' => $moduleName,
			'entity' => $entity,
		]);
		if ($this->enableExport) {
			$filename = $entity->name . '.php';
			file_put_contents($this->pathExport.'/'.$path.'/'.$filename, $output);
		} else {
			echo $output . PHP_EOL;
		}
	}

	protected function generateData($moduleName, $entity) {
		$path = 'module/'.$moduleName.'/src/Model';
		`mkdir -p $this->pathExport/$path`;

		$output = $this->twig->render('src/Model/data.php.twig', [
			'module' => $moduleName,
			'entity' => $entity,
		]);
		if ($this->enableExport) {
			$filename = $entity->name . 'Data.php';
			file_put_contents($this->pathExport.'/'.$path.'/'.$filename, $output);
		} else {
			echo $output . PHP_EOL;
		}
	}

	protected function generateTable($moduleName, $entity) {
		$path = 'module/'.$moduleName.'/src/Model';
		`mkdir -p $this->pathExport/$path`;

		$output = $this->twig->render('src/Model/table.php.twig', [// $actionCode
			'module' => $moduleName,
			'entity_name' => $entity->getName(),
			'entity' => $entity,
		]);
		if ($this->enableExport) {
			$filename = $entity->name . 'Table.php';
			file_put_contents($this->pathExport.'/'.$path.'/'.$filename, $output);
		} else {
			echo $output . PHP_EOL;
		}
	}

	protected function generateFilter($moduleName, $entity) {
		$path = 'module/'.$moduleName.'/src/Filter';
		`mkdir -p $this->pathExport/$path`;

		$output = $this->twig->render('src/Filter/filter.php.twig', [// $actionCode
			'module' => $moduleName,
			'entity' => $entity,
		]);
		if ($this->enableExport) {
			$filename = $entity->name . 'Filter.php';
			file_put_contents($this->pathExport.'/'.$path.'/'.$filename, $output);
		} else {
			echo $output . PHP_EOL;
		}
	}

	protected function generateTranslate($moduleName, $entity) {
		$path = 'module/'.$moduleName.'/language';
		`mkdir -p $this->pathExport/$path`;

		$output = $this->twig->render('language/translatable.php.twig', [// $actionCode
			'module' => $moduleName,
			'entity' => $entity,
		]);
		if ($this->enableExport) {
			$filename = $entity->name . '.en_US.php';
			file_put_contents($this->pathExport.'/'.$path.'/'.$filename, $output);
		} else {
			echo $output . PHP_EOL;
		}
	}

	public function generate($path, $enableExport=False) {
		$this->pathExport = $path;
		$this->enableExport = $enableExport;
		$moduleName = 'Application';

		foreach ($this->getEntities() as $entity) {
			$this->generateController($moduleName, $entity);
			$this->generateForm($moduleName, $entity);
			$this->generateObject($moduleName, $entity);
			$this->generateData($moduleName, $entity);
			$this->generateTable($moduleName, $entity);
			$this->generateFilter($moduleName, $entity);
			$this->generateTranslate($moduleName, $entity);
			//$this->generateHydrator($moduleName, $entity);
			//$this->generateValidator($moduleName, $entity);
			foreach ($this->config->crud as $crudName=>$actions) {
				$this->generateView($moduleName, $entity, $crudName);
			}
		}
	}
}

