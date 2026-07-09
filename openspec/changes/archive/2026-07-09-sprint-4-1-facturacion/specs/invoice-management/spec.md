# Invoice Management Specification

## Purpose

Automatically create, persist, and expose invoices and credit notes for paid orders and refunds, using organizer/year invoice numbering.

## Requirements

### Requirement: Automatic Invoice Creation

The system MUST create exactly one invoice when a payment is confirmed for an order.

#### Scenario: Paid order generates invoice

- GIVEN an order with a completed payment
- WHEN payment completion is processed
- THEN one invoice MUST be stored and linked to the order and payment

### Requirement: Invoice PDF Download

The system MUST allow authorized users to download the invoice as PDF.

#### Scenario: Invoice download is available

- GIVEN an existing invoice
- WHEN an authorized user requests the download
- THEN the system MUST return the PDF representation

### Requirement: Credit Notes

The system MUST create a credit note when a refund is processed.

#### Scenario: Refund creates credit note

- GIVEN a completed refund for a paid order
- WHEN refund processing finishes
- THEN a credit note MUST be created or updated for the refunded amount

### Requirement: Sequential Numbering

The system MUST assign invoice numbers sequentially per organizer and calendar year according to the configured series.

#### Scenario: Two invoices for the same organizer and year are created

- GIVEN two paid orders in sequence
- WHEN invoices are generated
- THEN the second invoice number MUST be greater than the first

#### Scenario: Different organizers keep independent series

- GIVEN two paid orders for different organizers in the same year
- WHEN invoices are generated
- THEN each organizer MUST receive its own sequential series

### Requirement: Exact Precision Billing

The system MUST calculate invoice amounts with exact precision for new billing flows.

#### Scenario: Invoice totals are calculated without float drift

- GIVEN an order with fractional monetary amounts
- WHEN the invoice is calculated
- THEN the stored totals MUST remain exact and deterministic
