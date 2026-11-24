import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { t } from "./CatalogPager-BwJ4cMnn.js";
import { t as n } from "./CatalogState-Bkly-MlG.js";
import { A as r, C as i, D as a, O as o, S as s, f as c, k as l } from "./catalog-DCoO3A8a.js";
import { t as ee } from "./ChannelFields-nZUWiNUS.js";
import { c as u, g as d, h as f, l as p, s as m } from "./structure-DbqcM-iO.js";
import { AtriumIntegrationScreen as h, AtriumPageHeader as te, AtriumState as ne } from "@refactor-circus/atrium";
import { UButton as re, UCard as ie, UFormField as ae, UInput as oe, ULink as se, UTable as ce } from "@refactor-circus/atrium/ui";
import { Fragment as le, computed as g, createBlock as ue, createElementBlock as _, createElementVNode as v, createTextVNode as y, createVNode as b, defineComponent as x, isRef as S, openBlock as C, reactive as w, ref as de, renderList as fe, toDisplayString as T, unref as E, withCtx as D, withModifiers as O } from "vue";
import { useI18n as k } from "vue-i18n";
import { useRoute as A } from "vue-router";
// src/pages/channels/index.vue?vue&type=script&setup=true&lang.ts
var j = {
	class: "space-y-4",
	"data-testid": "showroom-channels"
}, M = { class: "space-y-4" }, N = { class: "font-semibold" }, P = { class: "space-y-4" }, F = {
	key: 1,
	class: "flex flex-wrap gap-3"
}, I = ["data-testid"], L = { class: "font-mono" }, R = { class: "text-muted" }, z = { class: "font-semibold" }, B = { class: "flex flex-wrap items-end gap-3" }, V = { class: "space-y-4" }, pe = ["data-testid"], me = { class: "font-mono text-xs" }, he = { class: "font-mono text-xs" }, ge = { class: "font-mono text-xs" }, H = /* @__PURE__ */ x({
	__name: "index",
	setup(x) {
		let { t: H, locale: U } = k(), _e = A(), W = o(), G = o(), K = g(() => {
			let e = s(_e.query.cursor);
			return e ? { cursor: e } : {};
		}), { client: q, data: J, pending: ve, failed: ye, status: be, reload: xe } = l(async (e) => i(await e.GET("/channels", { params: { query: K.value } })), K), Y = l(async (e) => i(await e.GET("/locales", { params: { query: { per_page: 100 } } }))), Se = r(f), Ce = g(() => J.value?.data ?? []), X = g(() => Y.data.value?.data ?? []), Z = w({
			code: "",
			label: ""
		}), Q = de(u()), $ = w({
			code: "",
			label: ""
		}), we = g(() => [
			{
				accessorKey: "code",
				header: H("showroom.catalog.fields.code")
			},
			{
				id: "label",
				header: H("showroom.catalog.fields.label")
			},
			{
				accessorKey: "locales",
				header: H("showroom.structure.channels.locales")
			},
			{
				accessorKey: "currencies",
				header: H("showroom.structure.channels.currencies")
			},
			{
				accessorKey: "category_tree",
				header: H("showroom.structure.channels.category_tree")
			}
		]);
		async function Te() {
			let e = {
				code: Z.code.trim(),
				...d(U.value, Z.label),
				...m(Q.value)
			};
			q && await W.run(async () => i(await q.POST("/channels", { body: a(e) }))) && (Z.code = "", Z.label = "", Q.value = u(), await xe());
		}
		async function Ee() {
			let e = {
				code: $.code.trim(),
				...d(U.value, $.label)
			};
			q && await G.run(async () => i(await q.POST("/locales", { body: a(e) }))) && ($.code = "", $.label = "", await Y.reload());
		}
		async function De(e) {
			q && window.confirm(H("showroom.structure.channels.confirm_delete_locale", { code: e })) && await G.run(async () => i(await q.DELETE("/locales/{locale}", { params: { path: { locale: e } } }))) && await Y.reload();
		}
		function Oe(e) {
			let t = c(e.labels, U.value, e.code);
			return t === e.code ? "" : t;
		}
		return (r, i) => {
			let a = te, o = ne, s = re, l = oe, u = ae, d = ie, f = se, m = ce, g = h;
			return C(), _("div", j, [b(a, {
				title: E(H)("showroom.structure.channels.title"),
				description: E(H)("showroom.structure.channels.description")
			}, null, 8, ["title", "description"]), b(g, { "screen-key": "showroom.channels" }, {
				default: D(() => [v("div", M, [
					b(d, { "data-testid": "showroom-locales" }, {
						header: D(() => [v("h2", N, T(E(H)("showroom.structure.channels.locales")), 1)]),
						default: D(() => [b(n, {
							ready: !E(Y).pending.value && E(Y).data.value !== null,
							failed: E(Y).failed.value,
							status: E(Y).status.value,
							compact: ""
						}, {
							default: D(() => [v("div", P, [E(X).length === 0 ? (C(), ue(o, {
								key: 0,
								compact: "",
								title: E(H)("showroom.structure.channels.no_locales"),
								"data-testid": "showroom-locales-empty"
							}, null, 8, ["title"])) : (C(), _("ul", F, [(C(!0), _(le, null, fe(E(X), (e) => (C(), _("li", {
								key: e.code,
								class: "flex items-center gap-2 rounded-md border border-default px-2 py-1 text-sm",
								"data-testid": `showroom-locale-${e.code}`
							}, [
								v("span", L, T(e.code), 1),
								v("span", R, T(Oe(e)), 1),
								b(s, {
									variant: "ghost",
									size: "xs",
									icon: "i-lucide-trash-2",
									"aria-label": E(H)("showroom.catalog.delete"),
									loading: E(G).busy.value,
									"data-testid": `showroom-locale-delete-${e.code}`,
									onClick: (t) => De(e.code)
								}, null, 8, [
									"aria-label",
									"loading",
									"data-testid",
									"onClick"
								])
							], 8, I))), 128))])), v("form", {
								class: "flex flex-wrap items-end gap-3",
								"data-testid": "showroom-locale-create-form",
								onSubmit: O(Ee, ["prevent"])
							}, [
								b(u, {
									label: E(H)("showroom.catalog.fields.code"),
									hint: E(H)("showroom.structure.channels.locale_code_hint"),
									error: E(G).error("code"),
									required: ""
								}, {
									default: D(() => [b(l, {
										modelValue: E($).code,
										"onUpdate:modelValue": i[0] ||= (e) => E($).code = e,
										required: "",
										class: "w-44",
										"data-testid": "showroom-locale-create-code"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, [
									"label",
									"hint",
									"error"
								]),
								b(u, {
									label: E(H)("showroom.catalog.fields.label_in", { locale: E(U) }),
									error: E(G).error("labels")
								}, {
									default: D(() => [b(l, {
										modelValue: E($).label,
										"onUpdate:modelValue": i[1] ||= (e) => E($).label = e,
										class: "w-56",
										"data-testid": "showroom-locale-create-label"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, ["label", "error"]),
								b(s, {
									type: "submit",
									icon: "i-lucide-plus",
									loading: E(G).busy.value,
									"data-testid": "showroom-locale-create-submit"
								}, {
									default: D(() => [y(T(E(H)("showroom.structure.channels.add_locale")), 1)]),
									_: 1
								}, 8, ["loading"])
							], 32)])]),
							_: 1
						}, 8, [
							"ready",
							"failed",
							"status"
						]), b(e, {
							action: E(G),
							class: "mt-3"
						}, null, 8, ["action"])]),
						_: 1
					}),
					b(d, { "data-testid": "showroom-channel-new" }, {
						header: D(() => [v("h2", z, T(E(H)("showroom.structure.channels.new")), 1)]),
						default: D(() => [v("form", {
							class: "flex flex-col gap-4",
							"data-testid": "showroom-channel-create-form",
							onSubmit: O(Te, ["prevent"])
						}, [
							v("div", B, [b(u, {
								label: E(H)("showroom.catalog.fields.code"),
								hint: E(H)("showroom.structure.code_hint"),
								error: E(W).error("code"),
								required: ""
							}, {
								default: D(() => [b(l, {
									modelValue: E(Z).code,
									"onUpdate:modelValue": i[2] ||= (e) => E(Z).code = e,
									required: "",
									class: "w-56",
									"data-testid": "showroom-channel-create-code"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, [
								"label",
								"hint",
								"error"
							]), b(u, {
								label: E(H)("showroom.catalog.fields.label_in", { locale: E(U) }),
								error: E(W).error("labels")
							}, {
								default: D(() => [b(l, {
									modelValue: E(Z).label,
									"onUpdate:modelValue": i[3] ||= (e) => E(Z).label = e,
									class: "w-56",
									"data-testid": "showroom-channel-create-label"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label", "error"])]),
							b(ee, {
								modelValue: E(Q),
								"onUpdate:modelValue": i[4] ||= (e) => S(Q) ? Q.value = e : null,
								locales: E(X),
								trees: E(Se),
								action: E(W),
								testid: "showroom-channel-create"
							}, null, 8, [
								"modelValue",
								"locales",
								"trees",
								"action"
							]),
							v("div", null, [b(s, {
								type: "submit",
								icon: "i-lucide-plus",
								loading: E(W).busy.value,
								"data-testid": "showroom-channel-create-submit"
							}, {
								default: D(() => [y(T(E(H)("showroom.catalog.create")), 1)]),
								_: 1
							}, 8, ["loading"])])
						], 32), b(e, {
							action: E(W),
							class: "mt-3"
						}, null, 8, ["action"])]),
						_: 1
					}),
					b(n, {
						ready: !E(ve) && E(J) !== null,
						failed: E(ye),
						status: E(be)
					}, {
						default: D(() => [v("div", V, [b(m, {
							data: E(Ce),
							columns: E(we),
							"data-testid": "showroom-channels-table"
						}, {
							empty: D(() => [b(o, {
								compact: "",
								title: E(H)("showroom.structure.channels.empty"),
								"data-testid": "showroom-channels-empty"
							}, null, 8, ["title"])]),
							"code-cell": D(({ row: { original: e } }) => [v("span", { "data-testid": `showroom-channel-row-${e.code}` }, [b(f, {
								to: E(p)(e.code),
								class: "font-mono font-medium hover:underline"
							}, {
								default: D(() => [y(T(e.code), 1)]),
								_: 2
							}, 1032, ["to"])], 8, pe)]),
							"label-cell": D(({ row: { original: e } }) => [y(T(E(c)(e.labels, E(U), e.code)), 1)]),
							"locales-cell": D(({ row: { original: e } }) => [v("span", me, T((e.locales ?? []).join(", ")), 1)]),
							"currencies-cell": D(({ row: { original: e } }) => [v("span", he, T(e.currencies.join(", ")), 1)]),
							"category_tree-cell": D(({ row: { original: e } }) => [v("span", ge, T(e.category_tree ?? E(H)("showroom.catalog.none")), 1)]),
							_: 1
						}, 8, ["data", "columns"]), b(t, {
							next: E(J)?.meta?.next_cursor ?? null,
							prev: E(J)?.meta?.prev_cursor ?? null,
							testid: "showroom-channels"
						}, null, 8, ["next", "prev"])])]),
						_: 1
					}, 8, [
						"ready",
						"failed",
						"status"
					])
				])]),
				_: 1
			})]);
		};
	}
});
export { H as default };
