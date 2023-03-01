# API routes
`/api/get/friends`
**method**: `GET`
**description**: gets all poppy alive friends
**parameters**: `none`

`/api/get/types`
**method**: `GET`
**description**: gets all possible friend types
**parameters**: `none`

`/api/get/friendby`
**method**: `GET`
**description**: gets poppy friend(s) based on the given parameters
**parameters**:
- `string`: matches any friend attribute
- `JSON` containing any friend attribute key and its value
**examples**:
`friend 4`
will match friends named friend 4, friends with a friend 4 tag and friends with a friend 4 type
```json
{
    "name": "friend 4"
}
```
will match friends named friend 4
```json
{
    "name": "friend 4",
    "type": "GOD"
}
```
will match friends named friend 4 who are of the type GOD

`/api/post`
**method**: `POST`
**description**: creates a new friend with the given parameters
**parameters**:
- `JSON`: formatted as follows
```json
{
    "name": "name",
    "type": "type name",
    "value": "int|string",
    "tags": "string|[string1, string2]"
}
```
**examples**:
```json
{
    "name": "example friend 1",
    "type": "HOOMAN",
    "value": 11,
    "tags": ["tag1", "tag2"]
}
```

```json
{
    "name": "example friend 2",
    "type": "GOD",
    "value": "11",
    "tags": "example tag"
}
```

`/api/call/monster`
**method**: `GET`
**description**: calls the monster to eat one of poppy's friend,
- if you pass a friend ID the monster will target him
- if you pass nothing, the monster will target a random poppy friend
- if the given friend ID is incorrect the monster will also target a random poppy friend
- if the target is a god the monster will not eat him
- if the target is a unicorn the monster will not eat it
**parameters**:
`string`: matches a friend ID

`/api/get/dedFriends`
**method**: `GET`
**description**: gets all the friends who were eaten by the monster
**parameters**: `none`

`/api/update/value`
**method**: `POST`
**description**: updates the friendship value of a given friend with the given value
**parameters**:
- `JSON`: formatted as follow
```JSON
{
    "id": "validFriendId",
    "value": "int|string"
}
```
**example**:

```JSON
{
    "id": "myFriendId1",
    "value": 99
}
```
```JSON
{
    "id": "myFriendId2",
    "value": "88"
}
```

## dev only
`/setTestData`
**description**: sets basic test data