@javascript
Feature: Edit common attributes of many products at once
  In order to update many products with the same information
  As a product manager
  I need to be able to edit common attributes of many products at once

  Background:
    Given a "footwear" catalog configuration
    And the following family:
      | code       | attributes                                                       |
      | high_heels | sku, name, description, price, rating, size, color, manufacturer |
    And the following attributes:
      | code         | label       | type   | metric family | default metric unit | families                 |
      | weight       | Weight      | metric | Weight        | GRAM                | boots, sneakers, sandals |
      | heel_height  | Heel Height | metric | Length        | CENTIMETER          | high_heels, sandals      |
      | buckle_color | Buckle      | text   |               |                     | high_heels               |
    And the following product groups:
      | code          | label         | axis  | type    |
      | variant_heels | Variant Heels | color | VARIANT |
    And the following variant group values:
      | group         | attribute   | value         |
      | variant_heels | heel_height | 12 CENTIMETER |
    And the following products:
      | sku            | family     | color  | groups        |
      | boots          | boots      |        |               |
      | sneakers       | sneakers   |        |               |
      | sandals        | sandals    |        |               |
      | pump           |            |        |               |
      | highheels      | high_heels | red    | variant_heels |
      | blue_highheels | high_heels | blue   | variant_heels |
    And I am logged in as "Julia"
    And I am on the products page

  Scenario: Successfully update many text values at once
    Given I mass-edit products boots, sandals and sneakers
    And I choose the "Edit common attributes" operation
    And I display the Name attribute
    And I change the "Name" to "boots"
    And I move on to the next step
    And I wait for the "edit-common-attributes" mass-edit job to finish
    Then the english name of "boots" should be "boots"
    And the english name of "sandals" should be "boots"
    And the english name of "sneakers" should be "boots"

  Scenario: Successfully update many multi-valued values at once
    Given I mass-edit products boots and sneakers
    And I choose the "Edit common attributes" operation
    And I display the Weather conditions attribute
    And I change the "Weather conditions" to "Dry, Hot"
    And I move on to the next step
    And I wait for the "edit-common-attributes" mass-edit job to finish
    Then the options "weather_conditions" of products boots and sneakers should be:
      | value |
      | dry   |
      | hot   |

  @jira https://akeneo.atlassian.net/browse/PIM-3426
  Scenario: Successfully update multi-valued value at once where the product have already one of the value
    Given the following product values:
      | product | attribute          | value   |
      | boots   | weather_conditions | dry,hot |
    Given I mass-edit products boots and sneakers
    And I choose the "Edit common attributes" operation
    And I display the Weather conditions attribute
    And I change the "Weather conditions" to "Dry, Hot"
    And I move on to the next step
    And I wait for the "edit-common-attributes" mass-edit job to finish
    Then the options "weather_conditions" of products boots and sneakers should be:
      | value |
      | dry   |
      | hot   |

  @jira https://akeneo.atlassian.net/browse/PIM-3281
  Scenario: Successfully update localized values on selected locale
    Given I add the "french" locale to the "mobile" channel
    When I mass-edit products boots, sandals and sneakers
    And I choose the "Edit common attributes" operation
    And I switch the locale to "French (France)"
    And I display the [name] attribute
    And I change the "[name]" to "chaussure"
    And I move on to the next step
    And I wait for the "edit-common-attributes" mass-edit job to finish
    Then the french name of "boots" should be "chaussure"
    And the french name of "sandals" should be "chaussure"
    And the french name of "sneakers" should be "chaussure"

  @jira https://akeneo.atlassian.net/browse/PIM-3281
  Scenario: Successfully update localized and scoped values on selected locale
    Given I add the "french" locale to the "mobile" channel
    And I add the "french" locale to the "tablet" channel
    And I set product "pump" family to "boots"
    And I mass-edit products boots and pump
    And I choose the "Edit common attributes" operation
    And I switch the locale to "French (France)"
    And I display the [description] attribute
    And I expand the "[description]" attribute
    And fill in "pim_enrich_mass_edit_choose_action_operation_values_description_mobile_text" with "Foo Fr"
    And fill in "pim_enrich_mass_edit_choose_action_operation_values_description_tablet_text" with "Bar Fr"
    And I move on to the next step
    And I wait for the "edit-common-attributes" mass-edit job to finish
    Then the french mobile description of "boots" should be "Foo Fr"
    And the french tablet description of "boots" should be "Bar Fr"
    And the french mobile description of "pump" should be "Foo Fr"
    And the french tablet description of "pump" should be "Bar Fr"

  @jira https://akeneo.atlassian.net/browse/PIM-4528
  Scenario: See previously selected fields on mass edit error
    Given I mass-edit products boots and sandals
    And I choose the "Edit common attributes" operation
    And I display the Weight and Name attribute
    And I change the "Weight" to "Edith"
    And I move on to the next step
    Then I should see "Product information"
    And I should see "Weight"
    And I should see "Name"
    When I am on the attributes page
    And I am on the products page
    And I mass-edit products boots and sandals
    And I choose the "Edit common attributes" operation
    Then I should not see "Product information"
    And I should not see "Weight"
    And I should not see "Name"

  @jira https://akeneo.atlassian.net/browse/PIM-4777
  Scenario: Doing a mass edit of an attribute from a variant group does not override group value
    Given I mass-edit products highheels, blue_highheels and sandals
    And I choose the "Edit common attributes" operation
    And I display the Heel Height attribute
    And fill in "Heel Height" with "3"
    And I move on to the next step
    And I wait for the "edit-common-attributes" mass-edit job to finish
    Then the metric "heel_height" of products highheels, blue_highheels should be "12"
    And the metric "heel_height" of products sandals should be "3"
