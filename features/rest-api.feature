Feature: REST API

  Scenario: Making a GET request authenticated via basic authentication
	  Given I authenticate via basic authentication as "admin" with password "password"
    When I send a GET request to "http://behat.dev/wp-json/wp/v2/users/me"
    Then the response code should be 200

  Scenario: Making a GET request authenticated via basic authentication
	  Given I authenticate as "admin" with application password "dHE7 DDea BMRo 9k0V aOXa cu8h"
    When I send a GET request to "http://behat.dev/wp-json/wp/v2/users/me"
    Then the response code should be 200

  Scenario: Making an uathenticated GET request
    When I send a GET request to "http://behat.dev/wp-json/wp/v2/users/me"
    Then the response code should be 401

  Scenario: Making a GET request authenticated via Oauth1
    Given I authenticate via oauth 1
    When I send a GET request to "http://behat.dev/wp-json/wp/v2/users/me"
    Then the response code should be 200
