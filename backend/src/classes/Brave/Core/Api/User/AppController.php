<?php
namespace Brave\Core\Api\User;

use Brave\Core\Entity\App;
use Brave\Core\Entity\AppRepository;
use Brave\Core\Entity\Group;
use Brave\Core\Entity\GroupRepository;
use Brave\Core\Entity\Player;
use Brave\Core\Entity\PlayerRepository;
use Brave\Core\Service\UserAuthService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * @SWG\Tag(
 *     name="App",
 *     description="Application management."
 * )
 */
class AppController
{
    /**
     * @var Response
     */
    private $res;

    /**
     * @var LoggerInterface
     */
    private $log;

    /**
     * @var AppRepository
     */
    private $ar;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var App
     */
    private $app;

    /**
     * @var Player
     */
    private $player;

    /**
     * @var Group
     */
    private $group;

    public function __construct(Response $res, LoggerInterface $log, AppRepository $ar, EntityManagerInterface $em)
    {
        $this->res = $res;
        $this->log = $log;
        $this->ar = $ar;
        $this->em = $em;
    }

    /**
     * @SWG\Get(
     *     path="/user/app/all",
     *     operationId="all",
     *     summary="List all apps.",
     *     description="Needs role: app-admin",
     *     tags={"App"},
     *     security={{"Session"={}}},
     *     @SWG\Response(
     *         response="200",
     *         description="List of apps.",
     *         @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/App"))
     *     ),
     *     @SWG\Response(
     *         response="403",
     *         description="Not authorized."
     *     )
     * )
     */
    public function all(): Response
    {
        return $this->res->withJson($this->ar->findAll());
    }

    /**
     * @SWG\Post(
     *     path="/user/app/create",
     *     operationId="create",
     *     summary="Create an app.",
     *     description="Needs role: app-admin<br>Generates a random secret that must be changed by an app manager.",
     *     tags={"App"},
     *     security={{"Session"={}}},
     *     consumes={"application/x-www-form-urlencoded"},
     *     @SWG\Parameter(
     *         name="name",
     *         in="formData",
     *         required=true,
     *         description="Name of the app.",
     *         type="string",
     *         maxLength=255
     *     ),
     *     @SWG\Response(
     *         response="201",
     *         description="The new app.",
     *         @SWG\Schema(ref="#/definitions/App")
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="App name is invalid/missing."
     *     ),
     *     @SWG\Response(
     *         response="403",
     *         description="Not authorized."
     *     )
     * )
     */
    public function create(Request $request): Response
    {
        $name = $this->sanitize($request->getParam('name', ''));
        if ($name === '') {
            return $this->res->withStatus(400);
        }

        $app = new App();
        $app->setName($name);
        $app->setSecret(password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT));

        try {
            $this->em->persist($app);
            $this->em->flush();
        } catch (\Exception $e) {
            $this->log->critical($e->getMessage(), ['exception' => $e]);
            return $this->res->withStatus(500);
        }

        return $this->res->withJson($app);
    }

    /**
     * @SWG\Put(
     *     path="/user/app/{id}/rename",
     *     operationId="rename",
     *     summary="Rename an app.",
     *     description="Needs role: app-admin",
     *     tags={"App"},
     *     security={{"Session"={}}},
     *     consumes={"application/x-www-form-urlencoded"},
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the app.",
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="name",
     *         in="formData",
     *         required=true,
     *         description="New name for the app.",
     *         type="string",
     *         maxLength=64
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="App was renamed.",
     *         @SWG\Schema(ref="#/definitions/Group")
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="App not found."
     *     ),
     *     @SWG\Response(
     *         response="400",
     *         description="App name is invalid/missing."
     *     ),
     *     @SWG\Response(
     *         response="403",
     *         description="Not authorized."
     *     )
     * )
     */
    public function rename(string $id, Request $request): Response
    {
        $app = $this->ar->find((int) $id);
        if ($app === null) {
            return $this->res->withStatus(404);
        }

        $name = $this->sanitize($request->getParam('name', ''));
        if ($name === '') {
            return $this->res->withStatus(400);
        }

        $app->setName($name);
        try {
            $this->em->flush();
        } catch (\Exception $e) {
            $this->log->critical($e->getMessage(), ['exception' => $e]);
            return $this->res->withStatus(500);
        }

        return $this->res->withJson($app);
    }

    /**
     * @SWG\Delete(
     *     path="/user/app/{id}/delete",
     *     operationId="delete",
     *     summary="Delete an app.",
     *     description="Needs role: app-admin",
     *     tags={"App"},
     *     security={{"Session"={}}},
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the app.",
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response="204",
     *         description="App was deleted."
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="App not found."
     *     ),
     *     @SWG\Response(
     *         response="403",
     *         description="Not authorized."
     *     )
     * )
     */
    public function delete(string $id): Response
    {
        $app = $this->ar->find((int) $id);
        if ($app === null) {
            return $this->res->withStatus(404);
        }

        try {
            $this->em->remove($app);
            $this->em->flush();
        } catch (\Exception $e) {
            $this->log->critical($e->getMessage(), ['exception' => $e]);
            return $this->res->withStatus(500);
        }

        return $this->res->withStatus(204);
    }

    /**
     * @SWG\Get(
     *     path="/user/app/{id}/managers",
     *     operationId="managers",
     *     summary="List all managers of an app.",
     *     description="Needs role: app-admin",
     *     tags={"App"},
     *     security={{"Session"={}}},
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="App ID.",
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="List of players ordered by name. Only id and name properties are returned.",
     *         @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/Player"))
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="App not found."
     *     ),
     *     @SWG\Response(
     *         response="403",
     *         description="Not authorized."
     *     )
     * )
     */
    public function managers(string $id): Response
    {
        $ret = [];

        $app = $this->ar->find((int) $id);
        if ($app === null) {
            return $this->res->withStatus(404);
        }

        foreach ($app->getManagers() as $player) {
            $ret[] = [
                'id' => $player->getId(),
                'name' => $player->getName()
            ];
        }

        return $this->res->withJson($ret);
    }

    /**
     * @SWG\Put(
     *     path="/user/app/{id}/add-manager/{player}",
     *     operationId="addManager",
     *     summary="Assign a player as manager to an app.",
     *     description="Needs role: app-admin",
     *     tags={"App"},
     *     security={{"Session"={}}},
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the app.",
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="player",
     *         in="path",
     *         required=true,
     *         description="ID of the player.",
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response="204",
     *         description="Player added as manager."
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="Player and/or app not found."
     *     ),
     *     @SWG\Response(
     *         response="403",
     *         description="Not authorized."
     *     )
     * )
     */
    public function addManager(string $id, string $player, PlayerRepository $pr): Response
    {
        if (! $this->findAppAndPlayer($id, $player, $pr)) {
            return $this->res->withStatus(404);
        }

        $isManager = [];
        foreach ($this->app->getManagers() as $mg) {
            $isManager[] = $mg->getId();
        }
        if (! in_array($this->player->getId(), $isManager)) {
            $this->app->addManager($this->player);
        }

        try {
            $this->em->flush();
        } catch (\Exception $e) {
            $this->log->critical($e->getMessage(), ['exception' => $e]);
            return $this->res->withStatus(500);
        }

        return $this->res->withStatus(204);
    }

    /**
     * @SWG\Put(
     *     path="/user/app/{id}/remove-manager/{player}",
     *     operationId="removeManager",
     *     summary="Remove a manager (player) from an app.",
     *     description="Needs role: app-admin",
     *     tags={"App"},
     *     security={{"Session"={}}},
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the app.",
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="player",
     *         in="path",
     *         required=true,
     *         description="ID of the player.",
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response="204",
     *         description="Player removed from managers."
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="Player and/or app not found."
     *     ),
     *     @SWG\Response(
     *         response="403",
     *         description="Not authorized."
     *     )
     * )
     */
    public function removeManager(string $id, string $player, PlayerRepository $pr): Response
    {
        if (! $this->findAppAndPlayer($id, $player, $pr)) {
            return $this->res->withStatus(404);
        }

        $this->app->removeManager($this->player);

        try {
            $this->em->flush();
        } catch (\Exception $e) {
            $this->log->critical($e->getMessage(), ['exception' => $e]);
            return $this->res->withStatus(500);
        }

        return $this->res->withStatus(204);
    }

    /**
     * @SWG\Get(
     *     path="/user/app/{id}/groups",
     *     operationId="groups",
     *     summary="List all groups of an app.",
     *     description="Needs role: app-admin",
     *     tags={"App"},
     *     security={{"Session"={}}},
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="App ID.",
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="List of groups.",
     *         @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/Group"))
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="App not found."
     *     ),
     *     @SWG\Response(
     *         response="403",
     *         description="Not authorized."
     *     )
     * )
     */
    public function groups(string $id): Response
    {
        $ret = [];

        $app = $this->ar->find((int) $id);
        if ($app === null) {
            return $this->res->withStatus(404);
        }

        foreach ($app->getGroups() as $group) {
            $ret[] = $group;
        }

        return $this->res->withJson($ret);
    }

    /**
     * @SWG\Put(
     *     path="/user/app/{id}/add-group/{gid}",
     *     operationId="addGroup",
     *     summary="Add a group to an app.",
     *     description="Needs role: app-admin",
     *     tags={"App"},
     *     security={{"Session"={}}},
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the app.",
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="gid",
     *         in="path",
     *         required=true,
     *         description="ID of the group.",
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response="204",
     *         description="Group added to app."
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="Group and/or app not found."
     *     ),
     *     @SWG\Response(
     *         response="403",
     *         description="Not authorized."
     *     )
     * )
     */
    public function addGroup(string $id, string $gid, GroupRepository $gr): Response
    {
        if (! $this->findAppAndGroup($id, $gid, $gr)) {
            return $this->res->withStatus(404);
        }

        $hasGroups = [];
        foreach ($this->app->getGroups() as $gp) {
            $hasGroups[] = $gp->getId();
        }
        if (! in_array($this->group->getId(), $hasGroups)) {
            $this->app->addGroup($this->group);
        }

        try {
            $this->em->flush();
        } catch (\Exception $e) {
            $this->log->critical($e->getMessage(), ['exception' => $e]);
            return $this->res->withStatus(500);
        }

        return $this->res->withStatus(204);
    }

    /**
     * @SWG\Put(
     *     path="/user/app/{id}/remove-group/{gid}",
     *     operationId="removeGroup",
     *     summary="Remove a group from an app.",
     *     description="Needs role: app-admin",
     *     tags={"App"},
     *     security={{"Session"={}}},
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the app.",
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="gid",
     *         in="path",
     *         required=true,
     *         description="ID of the group.",
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response="204",
     *         description="Group removed from the app."
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="Group and/or app not found."
     *     ),
     *     @SWG\Response(
     *         response="403",
     *         description="Not authorized."
     *     )
     * )
     */
    public function removeGroup(string $id, string $gid, GroupRepository $gr): Response
    {
        if (! $this->findAppAndGroup($id, $gid, $gr)) {
            return $this->res->withStatus(404);
        }

        $this->app->removeGroup($this->group);

        try {
            $this->em->flush();
        } catch (\Exception $e) {
            $this->log->critical($e->getMessage(), ['exception' => $e]);
            return $this->res->withStatus(500);
        }

        return $this->res->withStatus(204);
    }

    /**
     * @SWG\Put(
     *     path="/user/app/{id}/change-secret",
     *     operationId="changeSecret",
     *     summary="Generates a new application secret. The new secret is returned, it cannot be retrieved afterwards.",
     *     description="Needs role: app-manager",
     *     tags={"App"},
     *     security={{"Session"={}}},
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the app.",
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="The new secret.",
     *         @SWG\Schema(type="string")
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="App not found."
     *     ),
     *     @SWG\Response(
     *         response="403",
     *         description="Not authorized."
     *     )
     * )
     */
    public function changeSecret(string $id, UserAuthService $uas): Response
    {
        $app = $this->ar->find((int) $id);
        if ($app === null) {
            return $this->res->withStatus(404);
        }

        // check if logged in user is manager
        $player = $uas->getUser()->getPlayer();
        if (! $app->isManager($player)) {
            return $this->res->withStatus(403);
        }

        $secret = bin2hex(random_bytes(32));
        $app->setSecret(password_hash($secret, PASSWORD_DEFAULT));

        try {
            $this->em->flush();
        } catch (\Exception $e) {
            $this->log->critical($e->getMessage(), ['exception' => $e]);
            return $this->res->withStatus(500);
        }

        return $this->res->withJson($secret);
    }

    private function findAppAndPlayer(string $id, string $player, PlayerRepository $pr): bool
    {
        $this->app = $this->ar->find((int) $id);
        $this->player = $pr->find((int) $player);

        if ($this->app === null || $this->player === null) {
            return false;
        }

        return true;
    }


    private function findAppAndGroup(string $id, string $gid, GroupRepository $gr): bool
    {
        $this->app = $this->ar->find((int) $id);
        $this->group = $gr->find((int) $gid);

        if ($this->app === null || $this->group === null) {
            return false;
        }

        return true;
    }

    private function sanitize($name): string
    {
        return str_replace(["\r", "\n"], ' ', trim($name));
    }
}
