<?php

/*
 * This file is part of the DataGridBundle.
 *
 * Iwan van Staveren <iwan@e-onesw.nl>
 */

namespace Sorien\DataGridBundle\Grid\Source;

use Sorien\DataGridBundle\Grid\Column\Column;
use Sorien\DataGridBundle\Grid\Rows;
use Sorien\DataGridBundle\Grid\Row;
use \ModelCriteria;
use \BasePeer;

class ModelSource extends Source
{

  /**
   * @var \ModelCriteria;
   */
  protected $query;

  /**
   * @var string e.g Vendor\Bundle\Model\Book
   */
  protected $class;

  /**
   * @var string e.g Book
   */
  protected $sourceName;

  /**
   * @var \Sorien\DataGridBundle\Grid\Mapping\Metadata\Metadata
   */
  private $metadata;

  const TABLE_ALIAS = '_a';
  const COUNT_ALIAS = '__count';

  /**
   * @param string $ModelName e.g Vendor\Bundle\Model\Book
   */
  public function __construct($sourceName)
  {
    $this->class = $sourceName;
    $this->joins = array();
  }

  public function initialise($container)
  {
    $mapping = $container->get('grid.mapping.manager');

    /** todo autoregister mapping drivers with tag */
    $mapping->addDriver($this, -1);

    $this->metadata = $mapping->getMetadata($this->class);
  }

  /**
   * @param \Sorien\DataGridBundle\Grid\Column\Column $column
   * @param boolean $withAlias
   * @return string
   */
  private function getFieldName($column, $withAlias = true)
  {
    $name = $column->getField();

    if ( strpos($name, '.') === false )
    {
      return $name;
    }

    $parent = self::TABLE_ALIAS;
    $elements = explode('.', $name);

    while ( $element = array_shift($elements) )
    {
      if ( count($elements) > 0 )
      {
        $this->joins['_' . $element] = $parent . '.' . $element;
        $parent = '_' . $element;
        $name = $element;
      }
      else
      {
        $name .= '.' . $element;
      }
    }


    if ( $withAlias )
    {
      return '_' . $name . ' as ' . $column->getId();
    }


    return '_' . $name;
  }

  protected function getPeerClassName()
  {
    return $this->class . "Peer";
  }

  /**
   * lookup the fieldnames from the peer class
   *
   * @param $class
   * @return array of field names
   */
  public function getClassColumns($class)
  {
    return call_user_func($class . 'Peer::getFieldNames', BasePeer::TYPE_PHPNAME);
  }

  /**
   * @param \Sorien\DataGridBundle\Grid\Columns $columns
   * @return null
   */
  public function getColumns($columns)
  {
    //$strPeer = $this->getPeerClassName(); call_user_func($strPeer."::getFieldNames")

    foreach ( $this->metadata->getColumnsFromMapping($columns) as $column )
    {
      $columns->addColumn($column);
    }
  }

  private function normalizeOperator($operator)
  {
    return ($operator == COLUMN::OPERATOR_REGEXP ? 'like' : $operator);
  }

  private function normalizeValue($operator, $value)
  {
    if ( $operator == COLUMN::OPERATOR_REGEXP )
    {
      preg_match('/\/\.\*([^\/]+)\.\*\//s', $value, $matches);
      return '\'%' . $matches[1] . '%\'';
    }
    else
    {
      return $value;
    }
  }

  /**
   * @param $columns \Sorien\DataGridBundle\Grid\Column\Column[]
   * @param $page int Page Number
   * @param $limit int Rows Per Page
   * @return \Sorien\DataGridBundle\Grid\Rows
   */
  public function execute($columns, $page = 0, $limit = 0)
  {
    $strQueryClass = $this->class . "Query";
    $strPeerClass  = $this->class . "Peer";

    $this->query = $strQueryClass::create();

    foreach ( $columns as $column )
    {
      $this->query->addSelectColumn(constant($strPeerClass . '::' . $this->getFieldName($column)));

      if ( $column->isSorted() )
      {
        $this->query->orderBy($this->getFieldName($column, false), $column->getOrder());
      }

      if ( $column->isFiltered() )
      {
        if ( $column->getFiltersConnection() == column::DATA_CONJUNCTION )
        {
          foreach ( $column->getFilters() as $filter )
          {
            $operator = $this->normalizeOperator($filter->getOperator());

            $this->query->filterBy($column, $filter->getValue(), $operator);
          }
        }
        elseif ( $column->getFiltersConnection() == column::DATA_DISJUNCTION )
        {
          $this->query->_or();
          foreach ( $column->getFilters() as $filter )
          {
            $operator = $this->normalizeOperator($filter->getOperator());

            $this->query->filterBy($column, $filter->getValue(), $operator);
          }
        }
      }
    }

    foreach ( $this->joins as $alias => $field )
    {
      $this->query->join($field, $alias);
    }

    if ( $page > 0 )
    {
      $this->query->offset($page * $limit);
    }

    if ( $limit > 0 )
    {
      $this->query->limit($limit);
    }

    //call overridden prepareQuery or associated closure
    $this->prepareQuery($this->query);

    //$this->query = \ProjectX\ModelBundle\Model\CompanyQuery::create();

    $items = $this->query->find();

//        $items = \ProjectX\ModelBundle\Model\CompanyQuery::create()->find();
    // hydrate result
    $result = new Rows();

    foreach ( $items as $item )
    {
      $row = new Row();

      foreach ( $item->toArray() as $key => $value )
      {
        $row->setField($key, $value);
      }

      //call overridden prepareRow or associated closure
      if ( ($modifiedRow = $this->prepareRow($row)) != null )
      {
        $result->addRow($modifiedRow);
      }
    }

    return $result;
  }

  public function getTotalCount($columns)
  {
    $query = $this->query;
    $query->limit(0)->offset(0);

    return $query->count();
  }

  public function getFieldsMetadata($class)
  {

    $tableMap = call_user_func($this->getPeerClassName() . "::getTableMap");

    $result = array();
    foreach ( $tableMap->getColumns() as $name )
    {
      $values = array('title' => $name->getPhpName(), 'source' => true);

      $values['field'] = $name->getName();
      $values['id'] = $name->getPhpName();

      $values['primary'] = $name->isPrimaryKey();


      switch ( $name->getType() )
      {
        case \PropelColumnTypes::CHAR:
        case \PropelColumnTypes::VARCHAR:
        case \PropelColumnTypes::LONGVARCHAR:
        case \PropelColumnTypes::CLOB:
        case \PropelColumnTypes::CLOB_EMU:
        case \PropelColumnTypes::NUMERIC:
        case \PropelColumnTypes::DECIMAL:
        case \PropelColumnTypes::TINYINT:
        case \PropelColumnTypes::SMALLINT:
        case \PropelColumnTypes::INTEGER:
        case \PropelColumnTypes::BIGINT:
        case \PropelColumnTypes::REAL:
        case \PropelColumnTypes::FLOAT:
        case \PropelColumnTypes::DOUBLE:
        case \PropelColumnTypes::BINARY:
        case \PropelColumnTypes::VARBINARY:
        case \PropelColumnTypes::LONGVARBINARY:
        case \PropelColumnTypes::BLOB:
        case \PropelColumnTypes::ENUM:
        case \PropelColumnTypes::OBJECT:
        case 'ARRAY':
          $values['type'] = 'text';
          break;
        case \PropelColumnTypes::DATE:
        case \PropelColumnTypes::TIME:
        case \PropelColumnTypes::TIMESTAMP:
        case \PropelColumnTypes::BU_DATE:
        case \PropelColumnTypes::BU_TIMESTAMP:
          $values['type'] = 'date';
          break;
        case \PropelColumnTypes::BOOLEAN:
        case \PropelColumnTypes::BOOLEAN_EMU:
          $values['type'] = 'boolean';
          break;
      }

      $result[$name->getPhpName()] = $values;
    }

    return $result;
  }

  public function getHash()
  {
    return $this->class;
  }

  public function delete(array $ids)
  {
    $peer = $this->getPeerClassName();

    foreach ( $ids as $id )
    {
      $object = call_user_func($peer . "::retrieveByPK", $id);

      if ( !$object )
      {
        throw new \Exception(sprintf('No %s found for id %s', $this->class, $id));
      }

      $object->delete();
    }
  }

}
