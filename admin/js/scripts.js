(function ($) {
  'use strict';

  const selectors = {
    attachmentId: 'input[name="' + sua_obj.input_name + '"]',
    attachmentAvatar: '.sua-attachment-avatar',
    attachmentDesc: '#sua-attachment-description',
    buttonAdd: '#btn-media-add',
    buttonRemove: '#btn-media-remove'
  };

  const elements = {
    attachmentId: $(selectors.attachmentId),
    attachmentAvatar: $(selectors.attachmentAvatar),
    attachmentDesc: $(selectors.attachmentDesc),
    buttonAdd: $(selectors.buttonAdd),
    buttonRemove: $(selectors.buttonRemove)
  };

  const preferredSizes = ['full', 'large', 'medium', 'thumbnail'];
  const defaultAvatarSrc = sua_obj.default_avatar_src || '';
  const defaultAvatarSrcSet = sua_obj.default_avatar_srcset || '';

  function updateAttachment(attachmentSrc, attachmentSrcSet, attachmentId) {
    const src = attachmentSrc || defaultAvatarSrc;
    const srcSet = attachmentSrcSet || defaultAvatarSrcSet;
    const resolvedId = attachmentId === undefined || attachmentId === null ? '' : parseInt(attachmentId, 10);

    if (!elements.attachmentAvatar.length) {
      return;
    }

    elements.attachmentAvatar.attr({
      src: src,
      srcset: srcSet
    });

    if (elements.attachmentId.length) {
      elements.attachmentId.val(resolvedId);
    }

    const hasCustomAvatar = !!resolvedId || !!src && src !== defaultAvatarSrc;

    elements.attachmentDesc.toggleClass('hidden', hasCustomAvatar);
    elements.buttonRemove.toggleClass('hidden', !hasCustomAvatar);
  }

  function getBestAttachmentUrl(attachment) {
    if (!attachment || !attachment.url) {
      return defaultAvatarSrc;
    }

    const sizes = attachment.sizes || {};

    for (let i = 0; i < preferredSizes.length; i++) {
      const size = preferredSizes[i];

      if (sizes[size] && sizes[size].url) {
        return sizes[size].url;
      }
    }

    return attachment.url;
  }

  $(function () {
    if (!window.wp || !wp.media || !wp.media.editor) {
      return;
    }

    $(document)
      .on('click', selectors.buttonAdd, function (event) {
        event.preventDefault();

        wp.media.editor.open();

        wp.media.editor.send.attachment = function (_, attachment) {
          const attachmentUrl = getBestAttachmentUrl(attachment);
          const attachmentId = attachment && attachment.id ? attachment.id : '';

          updateAttachment(attachmentUrl, attachmentUrl, attachmentId);
        };
      })
      .on('click', selectors.buttonRemove, function (event) {
        event.preventDefault();
        updateAttachment(defaultAvatarSrc, defaultAvatarSrcSet, '');
      })
      .on('click', selectors.attachmentAvatar, function (event) {
        event.preventDefault();
        elements.buttonAdd.trigger('click');
      });
  });
})(jQuery);
