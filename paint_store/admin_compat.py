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

# Propagate `template` when inclusion tags create a new context.
try:
    from django.template.library import InclusionNode as _InclusionNode

    _orig_inclusionnode_render = _InclusionNode.render

    def _inclusionnode_render_with_template(self, context):
        resolved_args, resolved_kwargs = self.get_resolved_arguments(context)
        _dict = self.func(*resolved_args, **resolved_kwargs)

        t = context.render_context.get(self)
        if t is None:
            if isinstance(self.filename, type(getattr(context, 'template', None))):
                t = self.filename
            elif hasattr(getattr(self.filename, 'template', None), '__class__') and getattr(self.filename, 'template', None):
                t = self.filename.template
            elif not isinstance(self.filename, str) and hasattr(self.filename, '__iter__'):
                t = context.template.engine.select_template(self.filename)
            else:
                t = context.template.engine.get_template(self.filename)
            context.render_context[self] = t

        new_context = context.new(_dict)
        try:
            new_context.template = getattr(context, 'template', None)
        except Exception:
            pass

        csrf_token = context.get('csrf_token')
        if csrf_token is not None:
            new_context['csrf_token'] = csrf_token
        return t.render(new_context)

    _InclusionNode.render = _inclusionnode_render_with_template
except Exception:
    pass

# Patch BaseContext.__copy__ so copies keep `template` and `render_context`.
try:
    from django.template import context as template_context

    _orig_basecopy = template_context.BaseContext.__copy__

    def _basecopy_with_template(self):
        duplicate = object.__new__(self.__class__)
        duplicate.dicts = self.dicts[:]
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
