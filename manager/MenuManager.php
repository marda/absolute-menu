<?php

namespace Absolute\Module\Menu\Manager;

use Absolute\Core\Manager\BaseManager;
use Absolute\Module\Menu\Entity\Menu;
use Absolute\Module\Team\Manager\TeamManager;
use Absolute\Module\Category\Manager\CategoryManager;
use Absolute\Module\User\Manager\UserManager;
use Nette\Database\Context;

class MenuManager extends BaseManager
{

    private $userManager,$categoryManager,$teamManager
            ;
    public function __construct(Context $database,UserManager $userManager,CategoryManager $categoryManager,TeamManager $teamManager)
    {
        parent::__construct($database);
        $this->categoryManager=$categoryManager;
        $this->userManager=$userManager;
        $this->teamManager=$teamManager;
    }
    
    public function getMenu($db, $onlyVisible = false){
        return _getMenu($db, $onlyVisible );
    }
    
    protected function _getMenu($db, $onlyVisible = false)
    {
        if ($db == false)
        {
            return false;
        }
        $object = new Menu($db->id, $db->menu_id, $db->type, $db->module, $db->name, $db->icon, $db->tooltip, $db->display, $db->menu_order, $db->created, $db->modified);
        /*if ($db->ref('page'))
        {
            $object->setPage($this->_getPage($db->ref('page')));
        }*/
        $subMenus = $db->related('menu');
        if ($onlyVisible)
        {
            $subMenus->where('display', true);
        }
        foreach ($subMenus as $menuDb)
        {
            $menu = $this->_getMenu($menuDb, $onlyVisible);
            if ($menu)
            {
                $object->addSubMenu($menu);
            }
        }
        foreach ($db->related('menu_user') as $userDb)
        {
            $user = $this->userManager->getUser($userDb->user);
            if ($user)
            {
                $object->addUser($user);
            }
        }
        foreach ($db->related('menu_team') as $teamDb)
        {
            $team = $this->teamManager->getTeam($teamDb->team);
            if ($team)
            {
                $object->addTeam($team);
            }
        }
        foreach ($db->related('menu_category') as $categoryDb)
        {
            $category = $this->categoryManager->getCategory($categoryDb->category);
            if ($category)
            {
                $object->addCategory($category);
            }
        }
        return $object;
    }

    /* INTERNAL METHODS */

    /* INTERNAL/EXTERNAL INTERFACE */

    public function _getById($id)
    {
        $resultDb = $this->database->table('menu')->get($id);
        return $this->_getMenu($resultDb);
    }

    private function _getListMain()
    {
        $ret = array();
        $resultDb = $this->database->table('menu')->where('menu_id IS NULL')->order('menu_order');
        foreach ($resultDb as $db)
        {
            $object = $this->_getMenu($db);
            $ret[] = $object;
        }
        return $ret;
    }

    private function _getList()
    {
        $ret = array();
        $resultDb = $this->database->table('menu')->order('menu_order');
        foreach ($resultDb as $db)
        {
            $object = $this->_getMenu($db);
            $ret[] = $object;
        }
        return $ret;
    }

    private function _getVisibleList()
    {
        $ret = array();
        $resultDb = $this->database->table('menu')->where('menu_id IS NULL')->where('display', true)->order('menu_order');
        foreach ($resultDb as $db)
        {
            $object = $this->_getMenu($db, true);
            $ret[] = $object;
        }
        return $ret;
    }

    // all connected user, users from teams, users from categories
    private function _getUserCanView($userId)
    {
        // SELECT menu_id FROM menu_user WHERE user_id = ? => Connected menu directly
        // UNION 
        // SELECT menu_id FROM menu_team JOIN team_user WHERE user_id = ? => Connected menu via teams
        // UNION 
        // SELECT menu_id FROM menu_category JOIN category_user WHERE user_id = ? => Connected menu via category
        // UNION 
        // SELECT id FROM menu WHERE id NOT IN (SELECT menu_id FROM menu_user) AND id NOT IN (SELECT menu_id FROM menu_team) AND id NOT IN (SELECT menu_id FROM menu_category) => Connected menu which are not to set to anyone (user, team, category)

        $db = $this->database->query("SELECT menu_id AS id FROM menu_user WHERE user_id = ? UNION SELECT menu_id AS id FROM menu_team JOIN team_user WHERE user_id = ? UNION SELECT menu_id AS id FROM menu_category JOIN category_user WHERE user_id = ? UNION SELECT id FROM menu WHERE id NOT IN (SELECT menu_id FROM menu_user) AND id NOT IN (SELECT menu_id FROM menu_team) AND id NOT IN (SELECT menu_id FROM menu_category)", $userId, $userId, $userId);
        $result = $db->fetchPairs("id", "id");
        return $result;
    }

    private function _getUserVisibleList($userId)
    {
        $menus = $this->_getVisibleList();

        $allowedMenus = $this->_getUserCanView($userId);
        $ret = [];
        foreach ($menus as $menu)
        {
            if (array_key_exists($menu->getId(), $allowedMenus))
            {
                $this->_removeUserNotAllowedSubmenus($menu, $allowedMenus);
                $ret[] = $menu;
            }
        }
        return $ret;
    }

    private function _removeUserNotAllowedSubmenus($menu, $allowedMenus)
    {
        foreach ($menu->subMenus as $sMenu)
        {
            if (!array_key_exists($sMenu->id, $allowedMenus))
            {
                $menu->removeSubMenu($sMenu->id);
                continue;
            }
            $this->_removeUserNotAllowedSubmenus($sMenu, $allowedMenus);
        }
    }

    /* EXTERNAL METHOD */

    public function getById($id)
    {
        return $this->_getById($id);
    }

    public function getList()
    {
        return $this->_getList();
    }

    public function getListMain()
    {
        return $this->_getListMain();
    }

    public function getVisibleList()
    {
        return $this->_getVisibleList();
    }

    public function getUserVisibleList($userId)
    {
        return $this->_getUserVisibleList($userId);
    }

    public function getUserCanView($userId)
    {
        return $this->_getUserCanView($userId);
    }

}
