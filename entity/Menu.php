<?php

namespace Absolute\Module\Menu\Entity;
use Absolute\Core\Entity\BaseEntity;

class Menu extends BaseEntity 
{

  private $id;
  private $name;
  private $module;
  private $icon;
  private $tooltip;
  private $display;
  private $created;
  private $order;
  private $modified;
  private $type;
  private $menuParentId;

  private $users = [];
  private $teams = [];
  private $categories = [];
  private $page = null;
  private $menu = null;
  private $subMenus = [];

	public function __construct($id, $menuParentId, $type, $module, $name, $icon, $tooltip, $display, $order, $created, $modified) 
  {
    $this->id = $id;
    $this->module = $module;
    $this->name = $name;
    $this->type = $type;
    $this->icon = $icon;
    $this->display = $display;
    $this->tooltip = $tooltip;
    $this->created = $created;
    $this->modified = $modified;
    $this->order = $order;
    $this->menuParentId = $menuParentId;
	}

  public function getId() 
  {
    return $this->id;
  }

  public function getIcon() 
  {
    return $this->icon;
  }

  public function getTooltip() 
  {
    return $this->tooltip;
  }

  public function getType() 
  {
    return $this->type;
  }

  public function getName() 
  {
    return $this->name;
  }

  public function getDisplay() 
  {
    return $this->display;
  }

  public function getCreated() 
  {
    return $this->created;
  }

  public function getModified() 
  {
    return $this->modified;
  }  

  public function getOrder()
  {
    return $this->order;
  }

  public function getModule()
  {
    return $this->module;
  }

  public function getPage()
  {
    return $this->page;
  }

  public function getMenu()
  {
    return $this->menu;
  }

  public function getSubMenus()
  {
    return $this->subMenus;
  }

  public function getUsers() 
  {
    return $this->users;
  }
  
  public function getTeams()
  {
    return $this->teams;
  }

  public function getCategories()
  {
    return $this->categories;
  }

  // SETTERS

  public function setPage($page)
  {
    $this->page = $page;
  }

  public function setMenu($menu)
  {
    $this->menu = $menu;
  }

  // REMOVERS

  public function removeSubMenu($menuId)
  {
    foreach ($this->subMenus as $key => $menu)
    {
      if ($menu->id == $menuId)
      {
        unset($this->subMenus[$key]);
      }
    }
  }

  // ADDERS

  public function addSubMenu($menu)
  {
    $this->subMenus[] = $menu;
  }

  public function addUser($user) 
  {
    $this->users[$user->id] = $user;
  }

  public function addTeam($team) 
  {
    $this->teams[$team->id] = $team;
  }

  public function addCategory($category) 
  {
    $this->categories[$category->getId()] = $category;
  }

  // OTHER METHODS

  public function toJsonString() 
  {
    return json_encode(array(
      "id" => $this->id,
      "name" => $this->name,
      "display" => $this->display,
      "icon" => $this->icon,
      "tooltip" => $this->tooltip,
      "module" => $this->module,
      "page" => ($this->page) ? $this->page->id : null,
      "menuParent" => $this->menuParentId,
      "order" => $this->order,
      "type" => $this->type,
      "created" => $this->created->format("F j, Y"),
      "modified" => $this->created->format("F j, Y"),
      "users" => array_values(array_map(function($user) { return $user->toJson(); }, $this->users)),
      "teams" => array_values(array_map(function($team) { return $team->toJson(); }, $this->teams)),
      "categories" => array_values(array_map(function($category) { return $category->toJson(); }, $this->categories)), 
    ));
  }

  public function toJson() 
  {
    return array(
      "id" => $this->id,
      "type" => $this->type,
      "name" => $this->name,
      "display" => $this->display,
      "created" => $this->created->format("F j, Y"),
      "modified" => $this->created->format("F j, Y"),
      "users" => array_values(array_map(function($user) { return $user->toJson(); }, $this->users)),
      "teams" => array_values(array_map(function($team) { return $team->toJson(); }, $this->teams)),
      "categories" => array_values(array_map(function($category) { return $category->toJson(); }, $this->categories)), 
    );
  }
}

