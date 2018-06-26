<?php

declare(strict_types=1);

namespace Tests\Sylius\CustomerReorderPlugin\Behat\Context\Reorder;

use Behat\Behat\Context\Context;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Session;
use Sylius\CustomerReorderPlugin\ReorderEligibility\ReorderEligibilityConstraintMessageFormatterInterface;
use Tests\Sylius\CustomerReorderPlugin\Behat\Page\SelectPaymentPageInterface;
use Tests\Sylius\CustomerReorderPlugin\Behat\Page\SelectShippingPageInterface;

final class ReorderContext implements Context
{
    /** @var Session */
    private $session;

    /** @var ReorderEligibilityConstraintMessageFormatterInterface */
    private $reorderEligibilityConstraintMessageFormatter;

    /** @var SelectShippingPageInterface */
    private $selectShippingPage;

    /** @var SelectPaymentPageInterface */
    private $selectPaymentPage;

    public function __construct(
        Session $session,
        ReorderEligibilityConstraintMessageFormatterInterface $reorderEligibilityConstraintMessageFormatter,
        SelectShippingPageInterface $selectShippingPage,
        SelectPaymentPageInterface $selectPaymentPage
    ) {
        $this->session = $session;
        $this->reorderEligibilityConstraintMessageFormatter = $reorderEligibilityConstraintMessageFormatter;
        $this->selectShippingPage = $selectShippingPage;
        $this->selectPaymentPage = $selectPaymentPage;
    }

    /**
     * @Then I should be able to reorder the order :orderNumber
     */
    public function iShouldSeeReorderButtonNextToTheOrder(string $orderNumber): void
    {
        $orderData = $this->session->getPage()->find('css', sprintf('tr:contains("%s")', $orderNumber));

        if (null === $orderData) {
            throw new \Exception(sprintf('There is no order %s on the orders list', $orderNumber));
        }

        $reorderButton = $orderData->find('css', sprintf('td button:contains("%s")', 'Reorder'));

        if (null === $reorderButton) {
            throw new \Exception(sprintf('There is no reorder button next to order %s', $orderNumber));
        }
    }

    /**
     * @When I reorder the order :orderNumber
     */
    public function iClickReorderButtonNextToTheOrder(string $orderNumber): void
    {
        $orderData = $this->session->getPage()->find('css', sprintf('tr:contains("%s")', $orderNumber));

        if (null === $orderData) {
            throw new \Exception(sprintf('There is no order %s on the orders list', $orderNumber));
        }

        $reorderButton = $orderData->find('css', sprintf('td button:contains("%s")', 'Reorder'));

        if (null === $reorderButton) {
            throw new \Exception(sprintf('There is no reorder button next to order %s', $orderNumber));
        }

        $reorderButton->click();
    }

    /**
     * @Then I should be notified that product :firstProduct is out of stock
     * @Then I should be notified that products :firstProduct, :secondProduct are out of stock
     */
    public function iShouldBeNotifiedThatProductIsOutOfStock(string ...$products): void
    {
        $this->assertFlashMessageWithTextExists(sprintf(
            'Following items: %s are out of stock, which have affected order total.',
            $this->reorderEligibilityConstraintMessageFormatter->format($products))
        );
    }

    /**
     * @Then I should be notified that product :productName is not available in expected quantity
     * @Then I should be notified that products :firstProduct, :secondProduct are not available in expected quantity
     */
    public function iShouldBeNotifiedThatUnitsOfProductWereAddedToCartInsteadOf(
        string ...$products
    ): void {
        $this->assertFlashMessageWithTextExists(sprintf(
            'Following items: %s are not available in expected quantity, which have affected order total.',
            $this->reorderEligibilityConstraintMessageFormatter->format($products)
        ));
    }

    /**
     * @Then I should be notified that :orderItemName price has changed
     */
    public function iShouldBeNotifiedThatOrderItemsPriceHasChanged(string $orderItemName): void
    {
        $this->assertFlashMessageWithTextExists(sprintf(
            'Prices of products: %s have changed, which have affected order total.',
            $orderItemName)
        );
    }

    /**
     * @Then I should be notified that previous order total was :orderTotal
     */
    public function iShouldBeNotifiedThatTotalPriceDiffersFromPreviouslyPlacedOrder(string $orderTotal): void
    {
        $this->assertFlashMessageWithTextExists(sprintf('Previous order total: %s', $orderTotal));
    }

    /**
     * @Then I should be notified that promotion :promotionName is no longer enabled
     */
    public function iShouldBeNotifiedThatPromotionIsNoLongerEnabled(string $promotionName): void
    {
        $this->assertFlashMessageWithTextExists(sprintf(
            'Following promotions: %s are no longer enabled, which have affected order total.',
            $promotionName)
        );
    }

    /**
     * @Then I should see exactly :count notification(s)
     */
    public function iShouldSeeExactlyNotifications(int $count): void
    {
        assert(count($this->session->getPage()->findAll('css', '.sylius-flash-message')) === $count);
    }

    /**
     * @Then I should not see any notifications
     */
    public function iShouldNotSeeAnyNotifications(): void
    {
        assert(count($this->session->getPage()->findAll('css', '.sylius-flash-message')) === 0);
    }

    /**
     * @Then I should not proceed to my cart summary page
     */
    public function iShouldNotProceedToMyCartSummaryPage(): void
    {
        assert(strpos($this->session->getCurrentUrl(), '/cart') === false);
    }

    /**
     * @Then I should be notified that none of items from previously placed order is available
     */
    public function iShouldBeNotifiedThatNoneOfItemsFromPreviouslyPlacedOrderIsAvailable(): void
    {
        $this->assertFlashMessageWithTextExists('None of items from previously placed order is available. Unable to place reorder.');
    }

    /**
     * @Then :shippingMethod shipping method should not be selected
     */
    public function shippingMethodShouldNotBeSelected(string $shippingMethod): void
    {
        assert($this->selectShippingPage->isShippingMethodSelected($shippingMethod) === false);
    }

    /**
     * @Then :paymentMethod payment method should not be selected
     */
    public function paymentMethodShouldNotBeSelected(string $paymentMethod): void
    {
        assert($this->selectPaymentPage->isPaymentMethodSelected($paymentMethod) === false);
    }

    /**
     * @When I proceed to the addressing step
     */
    public function iProceedToTheAddressingStep(): void
    {
        $this->session->getPage()->clickLink('Checkout');
    }

    /**
     * @When I proceed to the shipping step
     */
    public function iProceedToTheShippingStep(): void
    {
        $this->session->getPage()->find('css', '#next-step')->click();
    }

    /**
     * @When I proceed to the payment step
     */
    public function iProceedToThePaymentStep(): void
    {
        $this->session->getPage()->find('css', '#next-step')->click();
    }

    private function assertFlashMessageWithTextExists(string $text)
    {
        $notifications = $this->session->getPage()->findAll('css', '.sylius-flash-message');

        if (null === $notifications) {
            throw new \Exception('There is no notification on current page.');
        }

        /** @var NodeElement $notification */
        foreach ($notifications as $notification) {
            $message = $notification->getText();

            if (strpos($message, $text)) {
                return;
            }
        }

        throw new \Exception(sprintf('Flash message with text %s not found', $text));
    }
}
