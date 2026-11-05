/**
 * Kichik va xavfsiz markdown -> HTML. Avval butun matn escape qilinadi, keyin faqat
 * sarlavha (##, ###), **qalin**, *kursiv*, - ro'yxat, 1. ro'yxat, [matn](url) va xatboshi tiklanadi.
 * Havola faqat http(s) yoki "/" bilan boshlansa ishlaydi. Xom HTML hech qachon o'tmaydi.
 */
const escapeHtml = (s: string) => s
  .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
  .replace(/"/g, '&quot;').replace(/'/g, '&#39;')

function inline(escaped: string): string {
  return escaped
    .replace(/\[([^\]]+)\]\(([^)\s]+)\)/g, (_m, text: string, url: string) => {
      // url allaqachon escape qilingan (&amp; va h.k.), qo'shtirnoq &quot; bo'lgani uchun atributdan chiqib bo'lmaydi
      return /^(https?:\/\/|\/(?!\/))/i.test(url)
        ? `<a href="${url}" rel="noopener nofollow" target="_blank">${text}</a>`
        : text
    })
    .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
    .replace(/(^|[^*])\*([^*\s][^*]*)\*(?!\*)/g, '$1<em>$2</em>')
}

export function renderMarkdown(source: string): string {
  const lines = escapeHtml(source.replace(/\r\n?/g, '\n')).split('\n')
  const out: string[] = []
  let para: string[] = []
  let list: 'ul' | 'ol' | null = null

  const flushPara = () => {
    if (para.length) out.push(`<p>${inline(para.join(' '))}</p>`)
    para = []
  }
  const closeList = () => {
    if (list) out.push(`</${list}>`)
    list = null
  }

  for (const raw of lines) {
    const line = raw.trim()
    let m: RegExpMatchArray | null
    if (!line) {
      flushPara()
      closeList()
    }
    else if ((m = line.match(/^(#{1,4})\s+(.+)$/))) {
      flushPara()
      closeList()
      // H1 sahifada bitta (maqola sarlavhasi): matndagi # ham h2 bo'ladi
      const level = Math.min(Math.max(m[1]!.length, 2), 4)
      out.push(`<h${level}>${inline(m[2]!)}</h${level}>`)
    }
    else if ((m = line.match(/^[-*•]\s+(.+)$/))) {
      flushPara()
      if (list !== 'ul') {
        closeList()
        out.push('<ul>')
        list = 'ul'
      }
      out.push(`<li>${inline(m[1]!)}</li>`)
    }
    else if ((m = line.match(/^\d+[.)]\s+(.+)$/))) {
      flushPara()
      if (list !== 'ol') {
        closeList()
        out.push('<ol>')
        list = 'ol'
      }
      out.push(`<li>${inline(m[1]!)}</li>`)
    }
    else {
      closeList()
      para.push(line)
    }
  }
  flushPara()
  closeList()

  return out.join('\n')
}
