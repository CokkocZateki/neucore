<?php declare(strict_types=1);

namespace Neucore\Controller\User;

use Neucore\Controller\BaseController;
use Neucore\Entity\App;
use Neucore\Entity\Group;
use Neucore\Entity\Player;
use Neucore\Entity\Role;
use Neucore\Factory\RepositoryFactory;
use Neucore\Service\ObjectManager;
use Neucore\Service\Random;
use Neucore\Service\UserAuth;
use OpenApi\Annotations as OA;
use Psr\Log\LoggerInterface;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * @OA\Tag(
 *     name="App",
 *     description="Application management."
 * )
 */
class AppController extends BaseController
{
    /**
     * @var LoggerInterface
     */
    private $log;

    /**
     * @var RepositoryFactory
     */
    private $repositoryFactory;

    /**
     * @var App
     */
    private $application;

    /**
     * @var Player
     */
    private $player;

    /**
     * @var Group
     */
    private $group;

    /**
     * @var Role
     */
    private $role;

    private $availableRoles = [
        Role::APP_GROUPS,
        Role::APP_CHARS,
        Role::APP_TRACKING,
        Role::APP_ESI,
    ];

    public function __construct(
        Response $response,
        ObjectManager $objectManager,
        LoggerInterface $log,
        RepositoryFactory $repositoryFactory
    ) {
        parent::__construct($response, $objectManager);

        $this->log = $log;
        $this->repositoryFactory = $repositoryFactory;
    }

    /**
     * @OA\Get(
     *     path="/user/app/all",
     *     operationId="all",
     *     summary="List all apps.",
     *     description="Needs role: app-admin",
     *     tags={"App"},
     *     security={{"Session"={}}},
     *     @OA\Response(
     *         response="200",
     *         description="List of apps (only id and name properties are returned).",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/App"))
     *     ),
     *     @OA\Response(
     *         response="403",
     *         description="Not authorized."
     *     )
     * )
     */
    public function all(): Response
    {
        $apps = [];
        foreach ($this->repositoryFactory->getAppRepository()->findBy([]) as $app) {
            $apps[] = [
                'id' => $app->getId(),
                'name' => $app->getName(),
            ];
        }
        return $this->response->withJson($apps);
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
     *     ),
     *     @SWG\Response(
     *         response="500",
     *         description="If creation of app failed."
     *     )
     * )
     */
    public function create(Request $request): Response
    {
        $name = $this->sanitize($request->getParam('name', ''));
        if ($name === '') {
            return $this->response->withStatus(400);
        }

        $appRole = $this->repositoryFactory->getRoleRepository()->findOneBy(['name' => Role::APP]);
        if ($appRole === null) {
            $this->log->critical('AppController->create(): Role "'.Role::APP.'" not found.');
            return $this->response->withStatus(500);
        }

        $hash = password_hash(Random::hex(64), PASSWORD_BCRYPT);
        if ($hash === false) {
            return $this->response->withStatus(500);
        }

        $app = new App();
        $app->setName($name);
        $app->setSecret($hash);
        $app->addRole($appRole);

        $this->objectManager->persist($app);

        return $this->flushAndReturn(201, $app);
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
     *         @SWG\Schema(ref="#/definitions/App")
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
        $app = $this->repositoryFactory->getAppRepository()->find((int) $id);
        if ($app === null) {
            return $this->response->withStatus(404);
        }

        $name = $this->sanitize($request->getParam('name', ''));
        if ($name === '') {
            return $this->response->withStatus(400);
        }

        $app->setName($name);

        return $this->flushAndReturn(200, $app);
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
        $app = $this->repositoryFactory->getAppRepository()->find((int) $id);
        if ($app === null) {
            return $this->response->withStatus(404);
        }

        $this->objectManager->remove($app);

        return $this->flushAndReturn(204);
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
     *         description="List of players ordered by name. Only id, name and roles properties are returned.",
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

        $app = $this->repositoryFactory->getAppRepository()->find((int) $id);
        if ($app === null) {
            return $this->response->withStatus(404);
        }

        foreach ($app->getManagers() as $player) {
            $ret[] = [
                'id' => $player->getId(),
                'name' => $player->getName(),
                'roles' => $player->getRoles(),
            ];
        }

        return $this->response->withJson($ret);
    }

    /**
     * @SWG\Put(
     *     path="/user/app/{id}/add-manager/{pid}",
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
     *         name="pid",
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
    public function addManager(string $id, string $pid): Response
    {
        if (! $this->findAppAndPlayer($id, $pid)) {
            return $this->response->withStatus(404);
        }

        $isManager = [];
        foreach ($this->application->getManagers() as $mg) {
            $isManager[] = $mg->getId();
        }
        if (! in_array($this->player->getId(), $isManager)) {
            $this->application->addManager($this->player);
        }

        return $this->flushAndReturn(204);
    }

    /**
     * @SWG\Put(
     *     path="/user/app/{id}/remove-manager/{pid}",
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
     *         name="pid",
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
    public function removeManager(string $id, string $pid): Response
    {
        if (! $this->findAppAndPlayer($id, $pid)) {
            return $this->response->withStatus(404);
        }

        $this->application->removeManager($this->player);

        return $this->flushAndReturn(204);
    }

    /**
     * @SWG\Get(
     *     path="/user/app/{id}/show",
     *     operationId="show",
     *     summary="Shows app information.",
     *     description="Needs role: app-admin, app-manager
     *                  Managers can only see groups of their own apps.",
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
     *         description="The app information",
     *         @SWG\Schema(ref="#/definitions/App")
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
    public function show(string $id, UserAuth $uas): Response
    {
        $app = $this->repositoryFactory->getAppRepository()->find((int) $id);
        if ($app === null) {
            return $this->response->withStatus(404);
        }

        // check if logged in user is manager of this app or has the role app-admin
        $player = $this->getPlayer($uas);
        if (! $player->hasRole(Role::APP_ADMIN) && ! $app->isManager($player)) {
            return $this->response->withStatus(403);
        }

        return $this->response->withJson($app);
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
    public function addGroup(string $id, string $gid): Response
    {
        if (! $this->findAppAndGroup($id, $gid)) {
            return $this->response->withStatus(404);
        }

        $hasGroups = [];
        foreach ($this->application->getGroups() as $gp) {
            $hasGroups[] = $gp->getId();
        }
        if (! in_array($this->group->getId(), $hasGroups)) {
            $this->application->addGroup($this->group);
        }

        return $this->flushAndReturn(204);
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
    public function removeGroup(string $id, string $gid): Response
    {
        if (! $this->findAppAndGroup($id, $gid)) {
            return $this->response->withStatus(404);
        }

        $this->application->removeGroup($this->group);

        return $this->flushAndReturn(204);
    }

    /**
     * @SWG\Put(
     *     path="/user/app/{id}/add-role/{name}",
     *     operationId="addRole",
     *     summary="Add a role to the app.",
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
     *         name="name",
     *         in="path",
     *         required=true,
     *         description="Name of the role.",
     *         type="string",
     *         enum={"app-groups", "app-chars", "app-tracking", "app-esi"}
     *     ),
     *     @SWG\Response(
     *         response="204",
     *         description="Role added."
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="App and/or role not found or invalid."
     *     ),
     *     @SWG\Response(
     *         response="403",
     *         description="Not authorized."
     *     )
     * )
     */
    public function addRole(string $id, string $name)
    {
        if (! $this->findAppAndRole($id, $name)) {
            return $this->response->withStatus(404);
        }

        if (! $this->application->hasRole($this->role->getName())) {
            $this->application->addRole($this->role);
        }

        return $this->flushAndReturn(204);
    }

    /**
     * @SWG\Put(
     *     path="/user/app/{id}/remove-role/{name}",
     *     operationId="removeRole",
     *     summary="Remove a role from an app.",
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
     *         name="name",
     *         in="path",
     *         required=true,
     *         description="Name of the role.",
     *         type="string",
     *         enum={"app-groups", "app-chars", "app-tracking", "app-esi"}
     *     ),
     *     @SWG\Response(
     *         response="204",
     *         description="Role removed."
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="App and/or role not found or invalid."
     *     ),
     *     @SWG\Response(
     *         response="403",
     *         description="Not authorized."
     *     )
     * )
     */
    public function removeRole(string $id, string $name)
    {
        if (! $this->findAppAndRole($id, $name)) {
            return $this->response->withStatus(404);
        }

        $this->application->removeRole($this->role);

        return $this->flushAndReturn(204);
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
     *     ),
     *     @SWG\Response(
     *         response="500",
     *         description="Failed to created new secret."
     *     )
     * )
     */
    public function changeSecret(string $id, UserAuth $uas): Response
    {
        $app = $this->repositoryFactory->getAppRepository()->find((int) $id);
        if ($app === null) {
            return $this->response->withStatus(404);
        }

        // check if logged in user is manager
        if (! $app->isManager($this->getPlayer($uas))) {
            return $this->response->withStatus(403);
        }

        $secret = Random::hex(64);
        $hash = password_hash($secret, PASSWORD_BCRYPT);
        if ($hash === false) {
            return $this->response->withStatus(500);
        }

        $app->setSecret($hash);

        return $this->flushAndReturn(200, $secret);
    }

    private function findAppAndPlayer(string $id, string $player): bool
    {
        $player = $this->repositoryFactory->getPlayerRepository()->find((int) $player);
        if (! $this->findApp($id) || $player === null) {
            return false;
        }
        $this->player = $player;

        return true;
    }

    private function findAppAndGroup(string $id, string $gid): bool
    {
        $group = $this->repositoryFactory->getGroupRepository()->find((int) $gid);
        if (! $this->findApp($id) || $group === null) {
            return false;
        }
        $this->group = $group;

        return true;
    }

    private function findAppAndRole(string $id, string $name): bool
    {
        $role = $this->repositoryFactory->getRoleRepository()->findOneBy(['name' => $name]);
        if (! $this->findApp($id) || ! $role || ! in_array($role->getName(), $this->availableRoles)) {
            return false;
        }
        $this->role = $role;

        return true;
    }

    private function findApp($id)
    {
        $application = $this->repositoryFactory->getAppRepository()->find((int) $id);
        if (! $application) {
            return false;
        }
        $this->application = $application;

        return true;
    }

    private function sanitize($name): string
    {
        return str_replace(["\r", "\n"], ' ', trim($name));
    }

    private function getPlayer(UserAuth $userAuthService): Player
    {
        return $userAuthService->getUser()->getPlayer();
    }
}
