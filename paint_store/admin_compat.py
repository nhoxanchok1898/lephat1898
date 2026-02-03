"""Compatibility shim: ensure admin inclusion nodes can access a template.engine

This guards against environments where the template context object lacks a
`template` attribute (causing admin templatetags to raise AttributeError).
This is a small, non-invasive runtime shim for development only.
"""
from django.template import engines

# Ensure admin inclusion nodes and Template.render don't fail when a
# context object unexpectedly lacks a `template` attribute.
try:
    from django.contrib.admin.templatetags import base as admin_base

    _orig_inclusion_render = admin_base.InclusionAdminNode.render

    def _safe_inclusion_render(self, context):
        # Debug: record context type & template presence (temporary)
        try:
            print("admin_compat: InclusionAdminNode.render context=", type(context), "has_template=", hasattr(context, 'template'))
        except Exception:
            pass
        if not hasattr(context, "template") or context.template is None:
            try:
                django_engine = engines["django"].engine
                dummy = django_engine.from_string("")
                context.template = dummy
            except Exception:
                pass
        return _orig_inclusion_render(self, context)

    admin_base.InclusionAdminNode.render = _safe_inclusion_render
except Exception:
    # best effort; don't crash imports
    pass

# Make Template.render resilient if given a context without `template`.
try:
    from django.template import base as template_base

    _orig_template_render = template_base.Template.render

    def _safe_template_render(self, context):
        if not hasattr(context, "template"):
            try:
                django_engine = engines["django"].engine
                context.template = django_engine.from_string("")
            except Exception:
                pass
        return _orig_template_render(self, context)

    template_base.Template.render = _safe_template_render
except Exception:
    pass

# As a final safety-net, ensure BaseContext objects always expose a
# Template-like `template` attribute with an `engine` so any code that
"""Compatibility shim: lightweight, lazy fixes for admin template rendering.

This module applies a minimal set of idempotent runtime patches aimed at
avoiding AttributeError exceptions when admin templatetags access
``context.template``. The fixes are intentionally conservative and
deferred (created lazily at render time) so they work regardless of
import order during settings/app setup.

Only two small patches are applied:
- wrap `InclusionAdminNode.render` so a safe `template` is present on the
  context before admin rendering begins;
- wrap `Template.render` to retry once after attaching a dummy `template`
  if an AttributeError about ``template`` occurs.

These are development-time fallbacks; the long-term fix should be to
identify the code path that produces Context objects lacking `template`.
"""

from types import SimpleNamespace
import warnings
import logging

from django.template import engines

# Logger for admin_compat diagnostics
_LOG = logging.getLogger('paint_store.admin_compat')
if not _LOG.handlers:
    try:
        handler = logging.FileHandler('paint_store/admin_compat.log')
        handler.setFormatter(logging.Formatter('%(asctime)s %(levelname)s %(message)s'))
        _LOG.addHandler(handler)
        _LOG.setLevel(logging.INFO)
    except Exception:
        _LOG.addHandler(logging.NullHandler())


def _get_dummy_template():
    try:
        engine = engines["django"].engine
        return engine.from_string("")
    except Exception:
        return None


# Patch admin InclusionAdminNode.render to ensure a `template` is present
try:
    from django.contrib.admin.templatetags import base as admin_base

    if not getattr(admin_base.InclusionAdminNode.render, "__admin_compat_patched__", False):
        _orig_admin_inclusion_render = admin_base.InclusionAdminNode.render

        def _safe_admin_inclusion_render(self, context):
            try:
                if not hasattr(context, "template") or getattr(context, "template") is None:
                    dummy = _get_dummy_template()
                    if dummy is not None:
                        # Ensure BaseContext class exposes a template so instance
                        # attribute lookup won't raise for RequestContext/Context.
                        try:
                            from django.template import context as template_context
                            if not hasattr(template_context.BaseContext, 'template') or getattr(template_context.BaseContext, 'template') is None:
                                try:
                                    template_context.BaseContext.template = dummy
                                    _LOG.info('Set BaseContext.template to dummy template')
                                except Exception:
                                    _LOG.exception('Failed to set BaseContext.template')
                        except Exception:
                            _LOG.exception('Could not import template_context to set BaseContext.template')

                        try:
                            context.template = dummy
                        except Exception:
                            try:
                                setattr(type(context), "template", dummy)
                            except Exception:
                                # last-resort: attach an object with an `engine`
                                try:
                                    setattr(type(context), "template", SimpleNamespace(engine=dummy.engine))
                                except Exception:
                                    _LOG.exception('Failed to attach dummy.template to context type %s', type(context))
                                    try:
                                        _LOG.info('context attrs: %s', getattr(context, '__dict__', {}))
                                    except Exception:
                                        pass
            except Exception:
                _LOG.exception('Exception inside _safe_admin_inclusion_render for context type %s', type(context))
            return _orig_admin_inclusion_render(self, context)

        _safe_admin_inclusion_render.__admin_compat_patched__ = True
        admin_base.InclusionAdminNode.render = _safe_admin_inclusion_render
except Exception as exc:  # pragma: no cover - environment specific
    warnings.warn(f"admin_compat: could not patch admin InclusionAdminNode.render: {exc}")


# Patch Template.render to retry once after injecting a dummy `template`
try:
    from django.template import base as template_base

    if not getattr(template_base.Template.render, "__admin_compat_patched__", False):
        _orig_template_render = template_base.Template.render

        def _safe_template_render(self, context):
            try:
                return _orig_template_render(self, context)
            except AttributeError as err:
                msg = str(err)
                if "has no attribute 'template'" in msg or "object has no attribute 'template'" in msg:
                    _LOG.info('Template.render AttributeError for context type %s: %s', type(context), msg)
                    try:
                        _LOG.info('context hasattr(template)=%s; class_template=%s', hasattr(context, 'template'), getattr(type(context), 'template', None))
                        _LOG.info('context dict keys: %s', list(getattr(context, '__dict__', {}).keys()))
                    except Exception:
                        _LOG.exception('Failed to introspect context')
                    dummy = _get_dummy_template()
                    if dummy is not None:
                        try:
                            context.template = dummy
                            _LOG.info('Assigned dummy.template to instance of %s', type(context))
                        except Exception:
                            try:
                                setattr(type(context), "template", dummy)
                                _LOG.info('Assigned dummy.template to class %s', type(context))
                            except Exception:
                                try:
                                    setattr(type(context), "template", SimpleNamespace(engine=dummy.engine))
                                    _LOG.info('Assigned dummy.engine fallback to class %s', type(context))
                                except Exception:
                                    _LOG.exception('Failed to attach dummy template fallback to context type %s', type(context))
                    # retry original render
                    return _orig_template_render(self, context)
                raise

        _safe_template_render.__admin_compat_patched__ = True
        template_base.Template.render = _safe_template_render
except Exception as exc:  # pragma: no cover - environment specific
    warnings.warn(f"admin_compat: could not patch Template.render: {exc}")

# Ensure BaseContext exposes a minimal ``template`` at class level as a last-resort
try:
    from django.template import context as template_context
    dummy = _get_dummy_template()
    if dummy is not None:
        if not hasattr(template_context.BaseContext, "template") or getattr(template_context.BaseContext, "template") is None:
            try:
                template_context.BaseContext.template = dummy
            except Exception:
                # best-effort: set a lightweight object exposing an `engine`
                try:
                    from types import SimpleNamespace as _SN

                    template_context.BaseContext.template = _SN(engine=dummy.engine)
                except Exception:
                    pass
except Exception:
    # Do not fail imports for any reason
    pass

# Try again to set on RequestContext class as some environments expose that
try:
    from django.template.context import RequestContext as _RC
    dummy = _get_dummy_template()
    if dummy is not None:
        try:
            if not hasattr(_RC, 'template') or getattr(_RC, 'template') is None:
                try:
                    _RC.template = dummy
                except Exception:
                    try:
                        from types import SimpleNamespace as _SN

                        _RC.template = _SN(engine=dummy.engine)
                    except Exception:
                        pass
        except Exception:
            pass
except Exception:
    pass

