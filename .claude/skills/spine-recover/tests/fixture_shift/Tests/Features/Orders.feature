Feature: Orders

  Scenario: Cancel an order
    Given an order placed 5 days ago
    When a manager cancels the order
    Then the order is cancelled
