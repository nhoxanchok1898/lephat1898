"""Compatibility shim: ensure admin inclusion nodes can access a template.engine

This guards against environments where the template context object lacks a
`template` attribute (causing admin templatetags to raise AttributeError).
This is a small, non-invasive runtime shim for development only.
"""
from django.template import engines

try:
    from django.contrib.admin.templatetags import base as admin_base

    _orig_inclusion_render = admin_base.InclusionAdminNode.render

    def _safe_inclusion_render(self, context):
        # Some contexts (in rare reload/debug scenarios) may be missing the
        # `template` attribute expected by admin inclusion tags. Ensure a
        # dummy template with an Engine is available so select_template() can
        # be called safely.
        if not hasattr(context, "template") or context.template is None:
            try:
                django_engine = engines["django"].engine
                # create an empty Template instance bound to the django Engine
                dummy = django_engine.from_string("")
                context.template = dummy
            except Exception:
                # best-effort only; fall back to original behavior
                pass
        return _orig_inclusion_render(self, context)

    admin_base.InclusionAdminNode.render = _safe_inclusion_render
except Exception:
    # don't crash imports if admin isn't available yet
    pass

# As an additional safety-net, ensure Template.render can handle contexts
# that (for whatever reason) don't expose a `template` attribute.
try:
    from django.template import base as template_base

    _orig_template_render = template_base.Template.render

    def _safe_template_render(self, context):
        if not hasattr(context, "template"):
            try:
                # bind a minimal template object so template tags that
                # reference `context.template.engine` don't fail
                django_engine = engines["django"].engine
                context.template = django_engine.from_string("")
            except Exception:
                pass
        return _orig_template_render(self, context)

    template_base.Template.render = _safe_template_render
except Exception:
    pass

# Patch InclusionNode.render to propagate the `template` attribute to the
# new context created for inclusion tags. This avoids AttributeError in
# template rendering when code expects `context.template` to exist.
try:
    from django.template.library import InclusionNode as _InclusionNode

    _orig_inclusionnode_render = _InclusionNode.render

    def _inclusionnode_render_with_template(self, context):
        resolved_args, resolved_kwargs = self.get_resolved_arguments(context)
        _dict = self.func(*resolved_args, **resolved_kwargs)

        t = context.render_context.get(self)
        if t is None:
            if isinstance(self.filename, type(context.template)):
                t = self.filename
            elif hasattr(getattr(self.filename, "template", None), "__class__") and getattr(self.filename, "template", None):
                t = self.filename.template
            elif not isinstance(self.filename, str) and hasattr(self.filename, "__iter__"):
                t = context.template.engine.select_template(self.filename)
            else:
                t = context.template.engine.get_template(self.filename)
            context.render_context[self] = t

        new_context = context.new(_dict)
        # propagate template attribute if present
        try:
            new_context.template = getattr(context, 'template', None)
        except Exception:
            pass

        csrf_token = context.get("csrf_token")
        if csrf_token is not None:
            new_context["csrf_token"] = csrf_token
        return t.render(new_context)

    _InclusionNode.render = _inclusionnode_render_with_template
except Exception:
    pass

# Patch BaseContext.__copy__ to ensure copied contexts expose expected
# attributes like `template` and `render_context`. This prevents
# `AttributeError: 'RequestContext' object has no attribute 'template'`
# when inclusion tags create a new context via `context.new()`.
try:
    from django.template import context as template_context

    _orig_basecopy = template_context.BaseContext.__copy__

    def _basecopy_with_template(self):
        duplicate = object.__new__(self.__class__)
        duplicate.dicts = self.dicts[:]
        # ensure common attributes exist on the duplicate
        if hasattr(self, 'template'):
            duplicate.template = getattr(self, 'template')
        else:
            duplicate.template = None
        if hasattr(self, 'render_context'):
            duplicate.render_context = getattr(self, 'render_context')
        else:
            try:
                from django.template.context import RenderContext

                duplicate.render_context = RenderContext()
            except Exception:
                duplicate.render_context = None
        return duplicate

    template_context.BaseContext.__copy__ = _basecopy_with_template
except Exception:
    pass
