"""
Template Registry for Kayan ERP Centralized Printing Engine.
Provides explicit mapping from template identifiers to template implementations.
Fails explicitly if an unknown template is requested.
"""

from typing import Dict, List, Optional

try:
    from .templates.base_template import BasePrintTemplate
    from .templates.barcode_label_template import BarcodeLabelTemplate
    from .templates.purchase_invoice_template import PurchaseInvoiceTemplate
    from .templates.sales_invoice_template import SalesInvoiceTemplate
    from .templates.shift_closing_template import ShiftClosingTemplate
    from .templates.document_invoice_template import SalesInvoiceDocumentTemplate, PurchaseInvoiceDocumentTemplate
    from .templates.product_ledger_template import ProductLedgerDocumentTemplate
    from .templates.low_stock_template import LowStockDocumentTemplate
except (ImportError, ValueError):
    from templates.base_template import BasePrintTemplate
    from templates.barcode_label_template import BarcodeLabelTemplate
    from templates.purchase_invoice_template import PurchaseInvoiceTemplate
    from templates.sales_invoice_template import SalesInvoiceTemplate
    from templates.shift_closing_template import ShiftClosingTemplate
    from templates.document_invoice_template import SalesInvoiceDocumentTemplate, PurchaseInvoiceDocumentTemplate
    from templates.product_ledger_template import ProductLedgerDocumentTemplate
    from templates.low_stock_template import LowStockDocumentTemplate


class UnknownTemplateError(Exception):
    """Raised when an unrecognized template identifier is requested."""
    pass


class TemplateRegistry:
    """
    Registry holding available print templates.
    """

    def __init__(self):
        self._templates: Dict[str, BasePrintTemplate] = {}
        self._register_defaults()

    def _register_defaults(self):
        """Register default system templates."""
        self.register(BarcodeLabelTemplate())
        self.register(PurchaseInvoiceTemplate())
        self.register(SalesInvoiceTemplate())
        self.register(ShiftClosingTemplate())
        self.register(SalesInvoiceDocumentTemplate())
        self.register(PurchaseInvoiceDocumentTemplate())
        self.register(ProductLedgerDocumentTemplate())
        self.register(LowStockDocumentTemplate())

    def register(self, template: BasePrintTemplate) -> None:
        """Register a new template instance."""
        if not isinstance(template, BasePrintTemplate):
            raise TypeError(f"Template must inherit from BasePrintTemplate, got {type(template).__name__}")
        self._templates[template.template_id] = template

    def get(self, template_id: str) -> BasePrintTemplate:
        """
        Retrieve a template by its identifier.
        Raises UnknownTemplateError if the identifier is not found.
        """
        if not template_id:
            raise UnknownTemplateError("Template identifier cannot be empty.")

        clean_id = str(template_id).strip()
        if clean_id not in self._templates:
            available = ", ".join(sorted(self._templates.keys()))
            raise UnknownTemplateError(
                f"Unknown template identifier: '{clean_id}'. Available templates: [{available}]."
            )
        return self._templates[clean_id]

    def has(self, template_id: str) -> bool:
        """Check if a template exists in the registry."""
        return str(template_id).strip() in self._templates

    def list_templates(self) -> List[Dict[str, any]]:
        """Return a list of metadata for all registered templates."""
        return [
            {
                "template_id": t.template_id,
                "document_type": t.document_type,
                "description": t.description,
                "required_fields": t.required_fields,
            }
            for t in self._templates.values()
        ]


# Singleton global registry instance
registry = TemplateRegistry()
