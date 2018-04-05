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
                        "name" : "emails",
                        "type" : "string",
                        "in" : "formData",
                        "description" : 'This parameter must be a json array of email. Es: ["email1@example.com", "email2@example.com"]',
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


    window.swaggerUi.api.spec.paths["/plugins/paypal/job/{id_job}/{password}/instructions"] = {
        "get": {
            "tags": [
                "Job",
            ],
            "summary": "Read instructions for job",
            "description": "With this api you can read the instructions written for a job",
            "parameters" : [
                {
                    "name"     : "id_job",
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
            ],
            "responses": {
                "200": {
                    "description": "",
                },
                "default": {
                    "description": "Unexpected error"
                }
            }
        }
    }

    window.swaggerUi.api.spec.paths["/plugins/paypal/job/{id_job}/{password}/segments/{segments_ids}"] = {
        "get": {
            "tags": [
                "Job",
            ],
            "summary": "Get segments by ids",
            "description": "API to get segments details for a job by segments ids",
            "parameters" : [
                {
                    "name"     : "id_job",
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
                    "name"     : "segments_ids",
                    "type"     : "string",
                    "in"       : "formData",
                    "description": "Segments ids must to be separated by comma",
                    "required" : true,
                },
            ],
            "responses": {
                "200": {
                    "description": "Segments",
                    "schema": {
                        "$ref": "#/definitions/Segment"
                    }
                },
                "default": {
                    "description": "Unexpected error"
                }
            }
        }
    }

    window.swaggerUi.api.spec.paths["/api/new"].post.parameters.push( {
        "name": "instructions",
        "type": "string",
        "description": "Write here comments or instructions and they'll be shown in translate page",
        "in": "formData",
    } );

    window.swaggerUi.api.spec.paths["/api/new"].post.parameters.push( {
        "name": "project_type",
        "type": "string",
        "description": "Project type must to be one of this values: TR, LR or LQA",
        "in": "formData",
        "enum": ['TR', 'LR', 'LQA'],
    } );

    window.swaggerUi.api.spec.paths["/api/v1/new"].post.parameters.push( {
        "name": "instructions",
        "type": "string",
        "description": "Write here comments or instructions and they'll be shown in translate page",
        "in": "formData",
    } );

    window.swaggerUi.api.spec.paths["/api/v1/new"].post.parameters.push( {
        "name": "project_type",
        "type": "string",
        "description": "Project type must to be one of this values: TR, LR or LQA",
        "in": "formData",
        "enum": ['TR', 'LR', 'LQA'],
    } );


});