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

class ConfigGenerator {
	public $whiteTables = [
		//'accounts', 'companies', 'companies_associates',
		//'statements',
		// 'operations',
		//'operation_descriptions',
		'city',
	];

	public $crud = [
		'create' => ['add' => ['POST', 'GET']],
		'read'   => ['list'=> ['POST'],
			     'view'=> ['GET']],
		'update' => ['add' => ['POST', 'GET']],
		'delete' => ['del' => ['GET'],
			     'sup' => ['POST']],
	];
}

class BaseGenerator {
	protected $config;
	protected NamingStrategy $naming;

	protected $enableExport = False;

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
		$variabilizeFilter = new TwigFilter('variabilize', function($string, $pluralize=False) use($naming) {
			$string = $naming->entityify($string, $pluralize);
			
			$filter = new UnderscoreToCamelCase($string); // => 'operationDescription'
			$string = $filter->filter($string);
			$string = lcfirst($string);
			return '$'.$string;
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



		//$this->twig->addFilter($snake_caseFilter);

		$this->twig->addFilter($slugifyFilter);
		$this->twig->addFilter($entityfyFilter);
		$this->twig->addFilter($variabilizeFilter);
		$this->twig->addFilter($verbalizeFilter);// Humane readable, Humanize ?
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

	protected function generateController($moduleName, $controllerName=Null) {
		foreach ($this->getEntities() as $entity) {
			if (Null==$controllerName) {
				$controllerName = $entity->getName();
			}
			//$entity->getProperties('PK');
			//$entity->getProperties('FK & PK');
			//$entity->getProperties('FK ^ PK');
			$keys = $this->findKeys($entity->dbTable);
			$output = $this->twig->render('controller/crud.php.twig', [// $actionCode
				'module' => $moduleName,
				'crud' => $this->config->crud,
				'entity_name' => $entity->getName(),
				'primaryKey'        => $keys['primaries'],
				'primaryForeignKey' => $keys['foreignPrimaries'],
				'foreignKey'        => $keys['foreigns'],
			]);
			echo $output . PHP_EOL;
		}
	}

	protected function generateView($moduleName=Null, $actionName='index', $controllerName=Null) {
		foreach ($this->getEntities() as $table) {
			if (Null==$controllerName) {
				$controllerName = $this->naming->entityify($table->name);
			}
			
			// array reverse $this->config->mapActions = ['index' => 'list',...];
			// $viewName = $reverse[$actionName]

			$keys = $this->findKeys($table);
			$output = $this->twig->render('view/crud/update.php.twig', [// $actionCode
				'module' => $moduleName,
				'controller' => $controllerName,
				'action' => $actionName,
				'table' => $table,
				'primaryKey'        => $keys['primaries'],
				'primaryForeignKey' => $keys['foreignPrimaries'],
				'foreignKey'        => $keys['foreigns'],
			]);
			echo $output . PHP_EOL;
		}
	}

	public function generate($path) {
		$this->pathExport = realpath($path);
		$moduleName = 'Application';

		$this->generateController($moduleName);
		foreach ($this->config->crud as $verbe=>$actions) {

			/*
			//'POST'=>['create'=>'add', 'update'=>'edit', 'delete'=>'delete'],
			if ('POST'==$verbe) {
				foreach ($actions as $actionCode=>$actionName) {
					if ('update'==$actionCode) {
						$this->generateView($moduleName, $actionName);
					}
				}
			}
			*/
		}
	}
}


