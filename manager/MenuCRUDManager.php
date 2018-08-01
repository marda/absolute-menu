<?php

namespace Absolute\Module\Menu\Manager;

use Absolute\Core\Manager\BaseCRUDManager;

class MenuCRUDManager extends BaseCRUDManager
{

    public function __construct(\Nette\Database\Context $database)
    {
        parent::__construct($database);
    }

    // OTHER METHODS
    // CONNECT METHODS

    public function connectUsers($id, $users)
    {
        $users = array_unique(array_filter($users));
        // DELETE
        $this->database->table('menu_user')->where('menu_id', $id)->delete();
        // INSERT NEW
        $data = [];
        foreach ($users as $userId)
        {
            $data[] = array(
                "menu_id" => $id,
                "user_id" => $userId,
            );
        }

        if (!empty($data))
        {
            $this->database->table('menu_user')->insert($data);
        }
        return true;
    }

    public function connectTeams($id, $teams)
    {
        $teams = array_unique(array_filter($teams));
        // DELETE
        $this->database->table('menu_team')->where('menu_id', $id)->delete();
        // INSERT NEW
        $data = [];
        foreach ($teams as $team)
        {
            $data[] = [
                "team_id" => $team,
                "menu_id" => $id,
            ];
        }
        if (!empty($data))
        {
            $this->database->table("menu_team")->insert($data);
        }
        return true;
    }

    public function connectCategories($id, $categories)
    {
        $categories = array_unique(array_filter($categories));
        // DELETE
        $this->database->table('menu_category')->where('menu_id', $id)->delete();
        // INSERT NEW
        $data = [];
        foreach ($categories as $category)
        {
            $data[] = [
                "category_id" => $category,
                "menu_id" => $id,
            ];
        }
        if (!empty($data))
        {
            $this->database->table("menu_category")->insert($data);
        }
        return true;
    }

    // CUD METHODS

    public function show($id)
    {
        return $this->database->table('menu')->where('id', $id)->update(array('display' => true));
    }

    public function hide($id)
    {
        return $this->database->table('menu')->where('id', $id)->update(array('display' => false));
    }

    public function order($data)
    {
        $json = json_decode($data);
        if (!is_array($json))
        {
            return false;
        }
        $orders = [];
        $result = [];
        foreach ($json as $menuItem)
        {
            $orders[] = $menuItem->order;
        }
        sort($orders);
        foreach ($json as $menuItem)
        {
            $this->database->table('menu')->where('id', $menuItem->id)->update(array('menu_order' => array_shift($orders)));
        }
        return true;
    }

    public function create($module, $name, $icon, $tooltip, $pageId, $menuId, $type)
    {
        $ret = $this->database->table('menu')->insert(array(
            'name' => $name,
            'icon' => $icon,
            'tooltip' => $tooltip,
            'module' => $module,
            'page_id' => ($pageId) ? $pageId : null,
            'menu_id' => ($menuId) ? $menuId : null,
            'created' => new \DateTime(),
            'modified' => new \DateTime(),
            'type' => $type,
            'display' => true,
        ));
        if ($ret !== false)
        {
            $this->database->table('menu')->where('id', $ret->id)->update(array(
                'menu_order' => $ret->id
            ));
        }
        return $ret;
    }

    public function delete($id)
    {
        $this->database->table('menu_category')->where('menu_id', $id)->delete();
        $this->database->table('menu_team')->where('menu_id', $id)->delete();
        $this->database->table('menu_user')->where('menu_id', $id)->delete();
        return $this->database->table('menu')->where('id', $id)->delete();
    }

    public function update($id, $array)
    {
        if(isset($array['categories']))
            $this->connectCategories ($id, $array['categories']);
        if(isset($array['teams']))
            $this->connectTeams ($id, $array['teams']);
        if(isset($array['users']))
            $this->connectUsers ($id, $array['users']);
            
        unset($array['id']);
        unset($array['categories']);
        unset($array['teams']);
        unset($array['users']);
        
        $array['modified']=new \DateTime();
        if ($array["menu_id"] && !$this->_checkMenuRecursion($id, $array["menu_id"]))
        {
            $array["menu_id"] = null;
        }
        return $this->database->table('menu')->where('id', $id)->update($array);
    }

    // PRIVATE METHOD

    private function _checkMenuRecursion($id, $parentId)
    {
        if ($id == $parentId)
        {
            return false;
        }
        $parents = $this->database->query('SELECT q.id AS id, @pv:=q.menu_id AS menu_id FROM (SELECT * FROM menu ORDER BY id DESC) q JOIN (select @pv:=?) tmp WHERE q.id=@pv', $id)->fetchPairs('id', 'menu_id');
        $parentsParent = $this->database->query('SELECT q.id AS id, @pv:=q.menu_id AS menu_id FROM (SELECT * FROM menu ORDER BY id DESC) q JOIN (select @pv:=?) tmp WHERE q.id=@pv', $parentId)->fetchPairs('id', 'menu_id');
        if (array_key_exists($id, $parents) && $parents[$id] == $parentId)
        {
            unset($parents[$id]);
        }
        $parents = array_merge($parents, $parentsParent);
        if (in_array($parentId, $parents) || in_array($id, $parents))
        {
            return false;
        }
        return true;
    }

}
