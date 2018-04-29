<?php declare(strict_types=1);

namespace Brave\Core\Api\User;

use Brave\Core\Service\CharacterService;
use Brave\Core\Service\EsiApi;
use Brave\Core\Service\UserAuth;
use Slim\Http\Response;
use Brave\Core\Roles;
use Brave\Core\Entity\CharacterRepository;

/**
 * @SWG\Tag(
 *     name="Character",
 *     description="Character related functions."
 * )
 */
class CharacterController
{
    /**
     * @var Response
     */
    private $res;

    /**
     * @var UserAuth
     */
    private $uas;

    /**
     * @var EsiApi
     */
    private $es;

    /**
     * @var CharacterService
     */
    private $charService;

    /**
     * @var CharacterRepository
     */
    private $charRepo;

    public function __construct(Response $response, UserAuth $uas, CharacterService $cs,
        CharacterRepository $charRepo)
    {
        $this->res = $response;
        $this->uas = $uas;
        $this->charService = $cs;
        $this->charRepo = $charRepo;
    }

    /**
     * @SWG\Get(
     *     path="/user/character/show",
     *     operationId="show",
     *     summary="Returns the logged in EVE character.",
     *     description="Needs role: user",
     *     tags={"Character"},
     *     security={{"Session"={}}},
     *     @SWG\Response(
     *         response="200",
     *         description="The logged in EVE character.",
     *         @SWG\Schema(ref="#/definitions/Character")
     *     ),
     *     @SWG\Response(
     *         response="403",
     *         description="Not authorized"
     *     )
     * )
     */
    public function show(): Response
    {
        return $this->res->withJson($this->uas->getUser());
    }

    /**
     * @SWG\Put(
     *     path="/user/character/{id}/update",
     *     operationId="update",
     *     summary="Updates a character of the logged in player account with data from ESI.",
     *     description="Needs role: user or user-admin to update any character",
     *     tags={"Character"},
     *     security={{"Session"={}}},
     *     @SWG\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Character ID.",
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response="200",
     *         description="The updated character.",
     *         @SWG\Schema(ref="#/definitions/Character")
     *     ),
     *     @SWG\Response(
     *         response="404",
     *         description="Character not found on this account."
     *     ),
     *     @SWG\Response(
     *         response="403",
     *         description="Not authorized."
     *     ),
     *     @SWG\Response(
     *         response="503",
     *         description="ESI request failed."
     *     )
     * )
     */
    public function update(string $id): Response
    {
        $char = null;
        $player = $this->uas->getUser()->getPlayer();
        if ($player->hasRole(Roles::USER_ADMIN)) {
            $char = $this->charRepo->find((int) $id);
        } else {
            foreach ($this->uas->getUser()->getPlayer()->getCharacters() as $c) {
                if ($c->getId() === (int) $id) {
                    $char = $c;
                    break;
                }
            }
        }

        if ($char === null) {
            return $this->res->withStatus(404);
        }

        $updatedChar = $this->charService->fetchCharacter($char->getId(), true);
        if ($updatedChar === null) {
            return $this->res->withStatus(503);
        }

        return $this->res->withJson($updatedChar);
    }
}
