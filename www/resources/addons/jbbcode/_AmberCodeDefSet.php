<?php
	namespace JBBCode;

	require_once 'CodeDefinition.php';
	require_once 'CodeDefinitionBuilder.php';
	require_once 'CodeDefinitionSet.php';
	require_once 'validators/CssColorValidator.php';


	class AmberCodeDefSet implements CodeDefinitionSet {
		protected $definitions = array();

		public function __construct() {
			/* [b] bold tag */
			$builder = new CodeDefinitionBuilder('b', '<b>{param}</b>');
			array_push($this->definitions, $builder->build());

			/* [i] italics tag */
			$builder = new CodeDefinitionBuilder('i', '<i>{param}</i>');
			array_push($this->definitions, $builder->build());

			/* [u] underline tag */
			$builder = new CodeDefinitionBuilder('u', '<u>{param}</u>');
			array_push($this->definitions, $builder->build());

			/* [color] color tag */
			$builder = new CodeDefinitionBuilder('color', '<span style="color: {option}">{param}</span>');
			$builder->setUseOption(true)->setOptionValidator(new \JBBCode\validators\CssColorValidator());
			array_push($this->definitions, $builder->build());
		}

		public function getCodeDefinitions(){
			return $this->definitions;
		}
	}
