"""
Base template contract for the Centralized Python Printing Engine.
Every document template must inherit from BasePrintTemplate and implement render().
"""

from abc import ABC, abstractmethod
from typing import Any, Dict, List
from PIL import Image


class TemplateValidationError(Exception):
    """Raised when input data for a template fails validation."""
    pass


class BasePrintTemplate(ABC):
    """
    Abstract contract for document templates.
    """

    @property
    @abstractmethod
    def template_id(self) -> str:
        """Unique identifier of the template (e.g. 'barcode_label')."""
        pass

    @property
    @abstractmethod
    def document_type(self) -> str:
        """Logical category of document (e.g. 'barcode_label', 'purchase_invoice')."""
        pass

    @property
    @abstractmethod
    def description(self) -> str:
        """Human-readable description of the template and its intended target."""
        pass

    @property
    @abstractmethod
    def required_fields(self) -> List[str]:
        """List of field names that must be present in the job payload."""
        pass

    def validate(self, data: Dict[str, Any]) -> None:
        """
        Validate that all required fields exist and are non-empty.
        Subclasses may override to provide additional domain-specific validation.
        """
        if not isinstance(data, dict):
            raise TemplateValidationError(f"Job data must be a dictionary, got {type(data).__name__}.")

        missing = [f for f in self.required_fields if f not in data or data[f] is None or str(data[f]).strip() == ""]
        if missing:
            raise TemplateValidationError(
                f"Missing required field(s) for template '{self.template_id}': {', '.join(missing)}"
            )

    @abstractmethod
    def render(self, data: Dict[str, Any]) -> List[Image.Image]:
        """
        Render the document into one or more PIL Image objects.
        Returns a list of rendered page images ready for the printer spooler.
        """
        pass
