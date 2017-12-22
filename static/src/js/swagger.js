$(function () {
    window.swaggerUi.api.spec.paths["/v2/teams/{id_team}"] = {
            "put": {
                "tags": [
                    "Teams",
                ],
                "summary": "Update team",
                "description": "Update team.",
                "parameters" : [
                    {
                        "name"     : "id_team",
                        "type"     : "integer",
                        "in"       : "path",
                        "required" : true,
                    },
                    {
                        "name" : "name",
                        "type" : "string",
                        "in" : "fromData",
                        "required" : true
                    }
                ],
                "responses": {
                    "200": {
                        "description": "Team",
                        "schema": {
                            "$ref": "#/definitions/TeamItem"
                        }
                    },
                    "default": {
                        "description": "Unexpected error"
                    }
                }
            }
        }
});