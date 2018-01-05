$(function () {
    //console.log(window.swaggerUi.api.spec.paths);
    window.swaggerUi.api.spec.paths["/plugins/paypal/projects/{id_project}/{password}/whitelist"] = {
            "post": {
                "tags": [
                    "Project",
                ],
                "summary": "Add whitelist email to a project",
                "description": "With this API you can add a user by email to a project",
                "parameters" : [
                    {
                        "name"     : "id_project",
                        "type"     : "integer",
                        "in"       : "path",
                        "required" : true,
                    },
                    {
                        "name"     : "password",
                        "type"     : "string",
                        "in"       : "path",
                        "required" : true,
                    },
                    {
                        "name" : "email",
                        "type" : "string",
                        "in" : "fromData",
                        "description" : "This parameter must be a json array of email",
                        "required" : true
                    }
                ],
                "responses": {
                    "200": {
                        "description": "Internal id if success",
                    },
                    "default": {
                        "description": "Unexpected error"
                    }
                }
            },
            "delete": {
                "tags": [
                    "Project",
                ],
                "summary": "Delete whitelist emails in a project",
                "description": "With this API you can delete a user by email to a project",
                "parameters" : [
                    {
                        "name"     : "id_project",
                        "type"     : "integer",
                        "in"       : "path",
                        "required" : true,
                    },
                    {
                        "name"     : "password",
                        "type"     : "string",
                        "in"       : "path",
                        "required" : true,
                    }
                ],
                "responses": {
                    "200": {
                        "description": "True if success",

                    },
                    "default": {
                        "description": "Unexpected error"
                    }
                }
            },
        }
});