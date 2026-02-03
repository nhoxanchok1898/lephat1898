from decimal import Decimal, InvalidOperation
from django import template

register = template.Library()


@register.filter
def vnd(value):
    """
    Format number as VND with thousand separator (.) and trailing ₫.
    Example: 6800000 -> 6.800.000₫
    """
    try:
        number = Decimal(value)
    except (InvalidOperation, TypeError, ValueError):
        return value

    # Drop any fractional part for display
    number = number.quantize(Decimal("1"))
    formatted = f"{number:,.0f}".replace(",", ".")
    return f"{formatted}₫"
