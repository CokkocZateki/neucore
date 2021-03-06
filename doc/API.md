# API

All API endpoints from the backend are protected by roles.

The API is documented with OpenAPI, it is available at `https://[domain]/swagger.json`.

There is also an OpenAPI definition file available that contains only the API for applications at
[https://[domain]/application-api.json](https://neucore.herokuapp.com/application-api.json).

## Roles Overview

### User API

#### anonymous

This role is added automatically to every unauthenticated client (for `/api/user` endpoints, not apps),
it cannot be added to player accounts.

Auth API
- Result of last SSO attempt. `/user/auth/result`

Settings API
- List all settings. `/user/settings/system/list`

#### user

This role is added to all player accounts.

Auth API
- Result of last SSO attempt. `/user/auth/result`
- User logout. `/user/auth/logout`

Character API
- Return the logged in EVE character. `/user/character/show`
- Update a character with data from ESI. `/user/character/{id}/update`

Group API
- List all public groups. `/user/group/public`

Player API
- Return the logged in player with all properties. `/user/player/show`
- Check whether groups for this account are disabled or will be disabled soon. `/user/player/groups-disabled`
- Submit a group application. `/user/player/add-application/{gid}`
- Cancel a group application. `/user/player/remove-application/{gid}`
- Show all group applications. `/user/player/show-applications`
- Leave a group. `/user/player/leave-group/{gid}`
- Change the main character from the player account. `/user/player/set-main/{cid}`
- Delete a character. `/user/player/delete-character/{id}`

Settings API
- List all settings. `/user/settings/system/list`

#### user-admin

Allows a player to add and remove roles from players.

Character API
- Return a list of characters that matches the name (partial matching). `/user/character/find-by/{name}`
- Return the player to whom the character belongs. `/user/character/find-player-of/{id}`
- Update a character with data from ESI. `/user/character/{id}/update`

Player API
- List all players with characters. `/user/player/with-characters`
- List all players without characters. `/user/player/without-characters`
- Check whether groups for this account are disabled or will be disabled soon. `/user/player/{id}/groups-disabled`
- Delete a character. `/user/player/delete-character/{id}`
- Change the player's account status. `/user/player/{id}/set-status/{status}`
- Add a role to the player. `/user/player/{id}/add-role/{name}`
- Remove a role from a player. `/user/player/{id}/remove-role/{name}`
- Show all data from a player. `/user/player/{id}/show`
- List all players with a role. `/user/player/with-role/{name}`
- Lists all players with characters who have a certain status. `/user/player/with-status/{name}`

#### user-manager

Allows a player to add and remove groups from players with "managed" status.

Group API
- List all groups. `/user/group/all`
- Adds a player to a group. `/user/group/{id}/add-member/{pid}`
- Remove player from a group. `/user/group/{id}/remove-member/{pid}`

Player API
- Show all data from a player. `/user/player/{id}/show`
- Show player with characters. `/user/player/{id}/characters`
- Lists all players with characters who have a certain status. `/user/player/with-status/{name}`

#### group-admin

Allows a player to create groups and add and remove managers or corporation and alliances.

Alliance API
- List all alliances. `/user/alliance/all`
- List all alliances that have groups assigned. `/user/alliance/with-groups`
- Add an EVE alliance to the database. `/user/alliance/add/{id}`
- Add a group to the alliance. `/user/alliance/{id}/add-group/{gid}`
- Remove a group from the alliance. `/user/alliance/{id}/remove-group/{gid}`

Corporation API
- List all corporations. `/user/corporation/all`
- List all corporations that have groups assigned. `/user/corporation/with-groups`
- Add an EVE corporation to the database. `/user/corporation/add/{id}`
- Add a group to the corporation. `/user/corporation/{id}/add-group/{gid}`
- Remove a group from the corporation. `/user/corporation/{id}/remove-group/{gid}`

Group API
- List all groups. `/user/group/all`
- Create a group. `/user/group/create`
- Rename a group. `/user/group/{id}/rename`
- Change visibility of a group. `/user/group/{id}/set-visibility/{choice}`
- Delete a group. `/user/group/{id}/delete`
- List all managers of a group. `/user/group/{id}/managers`
- List all corporations of a group. `/user/group/{id}/corporations`
- List all alliances of a group. `/user/group/{id}/alliances`
- List all required groups of a group. `/user/group/{id}/required-groups`
- Add required group to a group. `/user/group/{id}/add-required/{groupId}`
- Remove required group from a group. `/user/group/{id}/remove-required/{groupId}`
- Assign a player as manager to a group. `/user/group/{id}/add-manager/{pid}`
- Remove a manager (player) from a group. `/user/group/{id}/remove-manager/{pid}`

Player API
- List all players with the role group-manger. `/user/player/group-managers`
- Show player with characters. `/user/player/{id}/characters`

#### group-manager

Allows a player to add and remove members to his groups.

Character API
- Return a list of characters that matches the name (partial matching). `/user/character/find-by/{name}`
- Return the player to whom the character belongs. `/user/character/find-player-of/{id}`

Group API
- List all required groups of a group. `/user/group/{id}/required-groups`
- List all applications of a group. `/user/group/{id}/applications`
- Accept a player's request to join a group. `/user/group/accept-application/{id}`
- Deny a player's request to join a group. `/user/group/deny-application/{id}`
- Adds a player to a group. `/user/group/{id}/add-member/{pid}`
- Remove player from a group. `/user/group/{id}/remove-member/{pid}`
- List all members of a group. `/user/group/{id}/members`

Player API
- Show player with characters. `/user/player/{id}/characters`

#### app-admin

Allows a player to create apps and add and remove managers and roles.

App API
- List all apps. `/user/app/all`
- Create an app. `/user/app/create`
- Shows app information. `/user/app/{id}/show`
- Rename an app. `/user/app/{id}/rename`
- Delete an app. `/user/app/{id}/delete`
- Add a group to an app. `/user/app/{id}/add-group/{gid}`
- Remove a group from an app. `/user/app/{id}/remove-group/{gid}`
- List all managers of an app. `/user/app/{id}/managers`
- Assign a player as manager to an app. `/user/app/{id}/add-manager/{pid}`
- Remove a manager (player) from an app. `/user/app/{id}/remove-manager/{pid}`
- Add a role to the app. `/user/app/{id}/add-role/{name}`
- Remove a role from an app. `/user/app/{id}/remove-role/{name}`

Group API
- List all groups. `/user/group/all`

Player API
- List all players with the role app-manger. `/user/player/app-managers`
- Show player with characters. `/user/player/{id}/characters`

#### app-manager

Allows a player to change the secret of his apps.

App API
- Shows app information. `/user/app/{id}/show`
- Generates a new application secret. The new secret is returned, it cannot be retrieved afterwards. `/user/app/{id}/change-secret`

#### esi

Allows a player to make an ESI request on behalf of a character from the database.

ESI API
- ESI request. `/user/esi/request`

#### settings

Allows a player to change the system settings.

Alliance API
- List all alliances. `/user/alliance/all`

Settings API
- Change a system settings variable. `/user/settings/system/change/{name}`
- Sends a 'Account disabled' test mail to the logged-in character. `/user/settings/system/send-account-disabled-mail`
- Validates ESI token from a director and updates name and corporation. `/user/settings/system/validate-director/{name}`

#### tracking

Allows a player to view corporation member tracking data.

Corporation API
- Returns all corporations that have member tracking data. `/user/corporation/tracked-corporations`
- Returns tracking data of corporation members. `/user/corporation/{id}/members`

Player API
- Show player with characters. `/user/player/{id}/characters`

### Application API

#### app

This role is added to all authenticated apps automatically. It
cannot be added to player accounts.

Application API
- Show app information. `/app/v1/show`

#### app-groups

Allows an app to get groups from a player account.

Application API
- Return groups of the character's player account. `/app/v1/groups/{cid}`
- Return groups of the character's player account. `/app/v2/groups/{cid}`
- Return groups of multiple players, identified by one of their character IDs. `/app/v1/groups`
- Return groups of the corporation. `/app/v1/corp-groups/{cid}`
- Return groups of the corporation. `/app/v2/corp-groups/{cid}`
- Return groups of multiple corporations. `/app/v1/corp-groups`
- Return groups of the alliance. `/app/v1/alliance-groups/{aid}`
- Return groups of the alliance. `/app/v2/alliance-groups/{aid}`
- Return groups of multiple alliances. `/app/v1/alliance-groups`
- Returns groups from the character's account, if available, or the corporation and alliance. `/app/v1/groups-with-fallback`

#### app-chars

Allows an app to get characters from a player account.

Application API
- Returns the main character of the player account to which the character ID belongs. `/app/v1/main/{cid}`
- Return the main character of the player account to which the character ID belongs. `/app/v2/main/{cid}`
- Return the player account to which the character ID belongs. `/app/v1/player/{characterId}`
- Return all characters of the player account to which the character ID belongs. `/app/v1/characters/{characterId}`
- Return all characters that were removed from the player account to which the character ID belongs. `/app/v1/removed-characters/{characterId}`

#### app-tracking

Allows an app to get corporation member tracking data.

Application API
- Return corporation member tracking data. `/app/v1/corporation/{id}/member-tracking`

#### app-esi

Allows an app to make an ESI request on behalf of a character from the database.

Application API
- Makes an ESI GET or POST request on behalf on an EVE character and returns the result. `/app/v1/esi`
  This endpoint can also be used with OpenAPI clients generated for ESI,
  see [app-esi-examples.php](app-esi-examples.php) for more.
