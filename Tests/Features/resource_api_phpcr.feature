Feature: PHPCR resource repository
    In order to retrieve data from the resource webservice
    As a webservice user
    I need to be able to query the webservice

    Background:
        Given the test application has the following configuration:
            """
            cmf_resource:
                repositories:
                    phpcr_repo:
                        type: doctrine_phpcr
                        basepath: /tests/cmf/articles

            cmf_resource_rest:
                enhancer_map:
                    -
                        repository: phpcr_repo
                        enhancer: payload
            """


    Scenario: Retrieve PHPCR resource with children
        Given there exists a "Article" document at "/cmf/articles/foo":
            | title | Article 1          |
            | body  | This is my article |
        When I send a GET request to "/api/phpcr_repo/foo"
        Then the response should contain json:
            """
            {
                "repository_alias": "phpcr_repo",
                "repository_type": "doctrine_phpcr",
                "payload_alias": null,
                "payload_type": "nt:unstructured",
                "path": "\/foo",
                "node_name": "foo",
                "label": "foo",
                "repository_path": "\/foo",
                "children": []
            }
            """

    Scenario: Rename a PHPCR resource
        Given there exists a "Article" document at "/cmf/articles/foo":
            | title | Article 1          |
            | body  | This is my article |
        When I send a PATCH request to "/api/phpcr_repo/foo" with body:
            """
            [{"operation": "move", "target": "/foo-bar"}]
            """
        Then the response code should be 204
        And there is an "Article" document at "/cmf/articles/foo-bar"
            | title | Article 1          |
            | body  | This is my article |

    Scenario: Move a PHPCR resource
        Given there exists a "Article" document at "/cmf/articles/foo":
            | title | Article 1          |
            | body  | This is my article |
        And there exists a "Article" document at "/cmf/articles/bar":
            | title | Article 2   |
            | body  | Another one |
        When I send a PATCH request to "/api/phpcr_repo/foo" with body:
            """
            [{"operation": "move", "target": "/bar/foo"}]
            """
        Then the response code should be 204
        And there is a "Article" document at "/cmf/articles/bar/foo"
            | title | Article 1          |
            | body  | This is my article |

    Scenario: Remove a PHPCR resource
        Given there exists a "Article" document at "/cmf/articles/foo":
            | title | Article 1          |
            | body  | This is my article |
        When I send a DELETE request to "/api/phpcr_repo/foo"
        Then the response code should be 204
        And there is no "Article" document at "/api/phpcr_repo/bar/foo"
