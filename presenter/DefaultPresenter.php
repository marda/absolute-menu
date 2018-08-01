<?php

namespace Absolute\Module\Menu\Presenter;

use Nette\Http\Response;
use Nette\Application\Responses\JsonResponse;
use Absolute\Module\Menu\Presenter\MenuBasePresenter;

class DefaultPresenter extends MenuBasePresenter
{

    /** @var \Absolute\Module\Menu\Manager\MenuCRUDManager @inject */
    public $menuCRUDManager;

    /** @var \Absolute\Module\Menu\Manager\MenuManager @inject */
    public $menuManager;

    public function startup()
    {
        parent::startup();
    }

    public function renderDefault($resourceId)
    {
        switch ($this->httpRequest->getMethod())
        {
            case 'GET':
                if ($resourceId != null)
                    $this->_getRequest($resourceId);
                else
                    $this->_getListRequest();
                break;
            case 'POST':
                $this->_postRequest();
                break;
            case 'PUT':
                if ($resourceId != null)
                    $this->_putRequest($resourceId);
                else
                    $this->_putRequestOrder();
                break;
            case 'DELETE':
                $this->_deleteRequest($resourceId);
            default:

                break;
        }
        $this->sendResponse(new JsonResponse(
                $this->jsonResponse->toJson(), "application/json;charset=utf-8"
        ));
    }

    private function _getRequest($id)
    {
        //if ($this->menuManager->canUserView($this->user->id))
        {
            $menu = $this->menuManager->getById($id);
            if (!$menu)
            {
                $this->httpResponse->setCode(Response::S404_NOT_FOUND);
                return;
            }
            $this->jsonResponse->payload = $menu->toJson();
            $this->httpResponse->setCode(Response::S200_OK);
        }
        //else
        //    $this->httpResponse->setCode(Response::S403_FORBIDDEN);
    }

    private function _getListRequest()
    {
        $menus = $this->menuManager->getList();
        $this->httpResponse->setCode(Response::S200_OK);

        $this->jsonResponse->payload = array_map(function($n)
        {
            return $n->toJson();
        }, $menus);
    }

    private function _putRequest($id)
    {
        $post = json_decode($this->httpRequest->getRawBody(), true);
        $this->jsonResponse->payload = [];
        $ret = $this->menuCRUDManager->update($id, $post);
        if (!$ret)
        {
            $this->httpResponse->setCode(Response::S500_INTERNAL_SERVER_ERROR);
        }
        else
        {
            $this->httpResponse->setCode(Response::S201_CREATED);
        }
    }

    private function _putRequestOrder()
    {
        if(!isset($id))
        {
            $this->httpResponse->setCode(Response::S400_BAD_REQUEST);
            return;
        } 
        $post = $this->httpRequest->getRawBody();
        $this->jsonResponse->payload = [];
        $ret = $this->menuCRUDManager->order($post);
        if (!$ret)
        {
            $this->httpResponse->setCode(Response::S500_INTERNAL_SERVER_ERROR);
        }
        else
        {
            $this->httpResponse->setCode(Response::S201_CREATED);
        }
    }

    private function _postRequest()
    {
        $post = json_decode($this->httpRequest->getRawBody());
        $ret = $this->menuCRUDManager->create($post->module, $post->name, $post->icon, $post->tooltip, $post->page_id, $post->menu_id, $post->type);
        if (!$ret)
        {
            $this->httpResponse->setCode(Response::S500_INTERNAL_SERVER_ERROR);
        }
        else
        {
            if (isset($post->categories))
                $this->menuCRUDManager->connectCategories($ret, $post->categories);
            if (isset($post->teams))
                $this->menuCRUDManager->connectTeams($ret, $post->teams);
            if (isset($post->users))
                $this->menuCRUDManager->connectUsers($ret, $post->users);
            
            $this->httpResponse->setCode(Response::S201_CREATED);
        }
    }

    private function _deleteRequest($id)
    {
        if(!isset($id))
        {
            $this->httpResponse->setCode(Response::S400_BAD_REQUEST);
            return;
        } 
        $this->menuCRUDManager->delete($id);
        $this->httpResponse->setCode(Response::S200_OK);
    }

}
