import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { t } from "./CatalogState-Bkly-MlG.js";
import { t as n } from "./ExternalIds-ATlFXCLK.js";
import { A as r, C as i, D as a, O as o, a as s, c, f as l, j as u, k as d, s as f } from "./catalog-DCoO3A8a.js";
import { u as p } from "./structure-DbqcM-iO.js";
import { t as m } from "./AssetThumbnail-DCu2iAUU.js";
import { i as h } from "./assets-DNUkY-zI.js";
import { n as g } from "./owners-C7wgNkGA.js";
import { AtriumIntegrationScreen as ee, AtriumPageHeader as te, AtriumState as _, navigateTo as v } from "@refactor-circus/atrium";
import { UBadge as ne, UButton as re, UCard as ie, UCheckbox as ae, UFormField as y, UIcon as oe, UInput as b, ULink as x, USelect as se } from "@refactor-circus/atrium/ui";
import { Fragment as S, computed as C, createBlock as w, createCommentVNode as T, createElementBlock as E, createElementVNode as D, createTextVNode as O, createVNode as k, defineComponent as A, isRef as j, openBlock as M, reactive as N, ref as P, renderList as F, shallowRef as I, toDisplayString as L, unref as R, useTemplateRef as z, watch as ce, withCtx as B, withModifiers as V } from "vue";
import { useI18n as H } from "vue-i18n";
import { useRoute as le } from "vue-router";
// src/components/owners/OwnerAssets.vue?vue&type=script&setup=true&lang.ts
var U = { class: "font-semibold" }, W = { class: "space-y-4" }, G = {
	key: 1,
	class: "grid grid-cols-2 gap-3 sm:grid-cols-4"
}, ue = /* @__PURE__ */ A({
	__name: "OwnerAssets",
	props: {
		assets: {},
		owner: {}
	},
	emits: ["changed"],
	setup(t, { emit: n }) {
		let r = t, a = n, { t: s } = H(), c = u(), l = o(), d = I(null), f = P("logo"), p = z("fileInput");
		function g(e) {
			d.value = e.target.files?.[0] ?? null;
		}
		function ee(e) {
			return [e.role, e.inherited ? s("showroom.catalog.inherited") : e.code].filter((e) => e).join(" · ");
		}
		async function te() {
			let e = d.value;
			c && e && await l.run(async () => {
				let t = new FormData();
				t.append("file", e);
				let n = i(await c.POST("/assets", {
					body: t,
					bodySerializer: (e) => e
				})).data;
				i(await c.POST("/assets/{asset}/links", {
					params: { path: { asset: n.code } },
					body: {
						type: "owner",
						target: r.owner,
						...f.value.trim() ? { role: f.value.trim() } : {}
					}
				}));
			}) && (d.value = null, p.value?.inputRef && (p.value.inputRef.value = ""), a("changed"));
		}
		return (t, n) => {
			let i = _, a = x, o = b, c = y, u = re, d = ie;
			return M(), w(d, { "data-testid": "showroom-owner-assets" }, {
				header: B(() => [D("h2", U, L(R(s)("showroom.catalog.assets.title")), 1)]),
				default: B(() => [D("div", W, [
					r.assets.length === 0 ? (M(), w(i, {
						key: 0,
						compact: "",
						title: R(s)("showroom.catalog.assets.none"),
						"data-testid": "showroom-owner-assets-empty"
					}, null, 8, ["title"])) : (M(), E("div", G, [(M(!0), E(S, null, F(r.assets, (e) => (M(), w(a, {
						key: `${e.inherited ? "inherited" : "own"}-${e.code}`,
						to: R(h)(e.code),
						class: "flex flex-col gap-2 rounded-md border border-default p-2 hover:border-primary",
						"data-testid": `showroom-asset-${e.code}`
					}, {
						default: B(() => [k(m, {
							asset: {
								filename: e.code,
								mime_type: e.mime_type,
								url: e.url ?? ""
							},
							caption: ee(e)
						}, null, 8, ["asset", "caption"])]),
						_: 2
					}, 1032, ["to", "data-testid"]))), 128))])),
					k(e, { action: R(l) }, null, 8, ["action"]),
					D("form", {
						class: "flex flex-wrap items-start gap-3",
						"data-testid": "showroom-asset-upload",
						onSubmit: V(te, ["prevent"])
					}, [
						k(c, {
							label: R(s)("showroom.catalog.assets.upload_and_link"),
							error: R(l).error("file")
						}, {
							default: B(() => [k(o, {
								ref: "fileInput",
								type: "file",
								required: "",
								"data-testid": "showroom-asset-file",
								onChange: g
							}, null, 512)]),
							_: 1
						}, 8, ["label", "error"]),
						k(c, {
							label: R(s)("showroom.catalog.assets.role"),
							error: R(l).error("role")
						}, {
							default: B(() => [k(o, {
								modelValue: R(f),
								"onUpdate:modelValue": n[0] ||= (e) => j(f) ? f.value = e : null,
								class: "w-32",
								"data-testid": "showroom-asset-role"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label", "error"]),
						k(u, {
							type: "submit",
							icon: "i-lucide-upload",
							loading: R(l).busy.value,
							class: "mt-field-label",
							"data-testid": "showroom-asset-upload-submit"
						}, {
							default: B(() => [O(L(R(s)("showroom.catalog.assets.upload")), 1)]),
							_: 1
						}, 8, ["loading"])
					], 32)
				])]),
				_: 1
			});
		};
	}
}), de = {
	class: "space-y-4",
	"data-testid": "showroom-owner"
}, fe = {
	key: 0,
	class: "space-y-5"
}, pe = {
	key: 0,
	class: "flex flex-wrap items-center gap-1 text-sm text-muted",
	"data-testid": "showroom-owner-chain"
}, me = { class: "font-mono text-default" }, he = {
	class: "truncate",
	"data-testid": "showroom-owner-title"
}, ge = {
	class: "font-mono text-sm",
	"data-testid": "showroom-owner-subtitle"
}, _e = {
	class: "text-sm text-muted",
	"data-testid": "showroom-owner-counts"
}, ve = { class: "font-semibold" }, ye = { class: "flex flex-wrap items-start gap-4" }, be = { class: "font-semibold" }, xe = { class: "flex flex-col gap-4" }, Se = { class: "flex flex-wrap items-baseline gap-1 text-sm" }, Ce = { class: "text-muted" }, we = {
	class: "font-mono",
	"data-testid": "showroom-owner-slug"
}, Te = {
	key: 0,
	"data-testid": "showroom-own-flags"
}, Ee = { class: "mb-1 text-xs font-medium uppercase text-muted" }, De = { class: "flex flex-wrap gap-3" }, Oe = { class: "font-mono text-xs" }, ke = {
	key: 0,
	class: "ms-1.5 text-muted"
}, Ae = {
	key: 0,
	class: "text-sm text-muted"
}, je = {
	key: 0,
	class: "text-xs text-error"
}, Me = { "data-testid": "showroom-effective-flags" }, Ne = { class: "mb-1 text-xs font-medium uppercase text-muted" }, Pe = { class: "flex flex-wrap gap-3" }, Fe = ["data-effective-flag"], Ie = {
	key: 0,
	class: "text-xs text-muted"
}, Le = {
	key: 0,
	class: "text-sm text-muted"
}, Re = { class: "grid gap-5 lg:grid-cols-2" }, ze = { class: "font-semibold" }, Be = { class: "space-y-4" }, Ve = {
	key: 0,
	class: "text-sm text-muted"
}, He = {
	key: 1,
	class: "flex flex-wrap gap-2"
}, Ue = {
	key: 2,
	class: "text-xs text-error"
}, K = /* @__PURE__ */ A({
	__name: "[owner]",
	setup(u) {
		let { t: m, locale: h } = H(), _ = le(), A = o(), I = o(), z = C(() => String(_.params.owner)), { client: U, data: W, pending: G, failed: K, status: We, reload: q } = d(async (e) => i(await e.GET("/owners/{owner}", { params: { path: { owner: z.value } } })).data, z), J = C(() => W.value), Ge = C(() => (J.value?.chain ?? []).slice(0, -1)), Y = r(async (e) => i(await e.GET("/owner-types", { params: { query: { per_page: 100 } } }))), Ke = r(async (e) => i(await e.GET("/flags", { params: { query: { per_page: 100 } } }))), X = C(() => Ke.value.filter((e) => e.applies_to.includes("owner"))), qe = C(() => Je.value.map((e) => ({
			label: Xe(e.code),
			value: e.code
		}))), Je = C(() => Y.value.filter((e) => e.parent_types === null || e.parent_types === void 0 || e.parent_types.includes(J.value?.type ?? ""))), Z = N({
			label: "",
			parent: "",
			enabled: !0,
			flags: []
		}), Q = P(c(null)), $ = N({
			code: "",
			type: ""
		});
		ce(W, () => {
			let e = J.value;
			e && (Z.label = e.labels[h.value] ?? "", Z.parent = e.parent ?? "", Z.enabled = e.enabled, Z.flags = [...e.flags ?? []], Q.value = c(e.external_ids));
		}, { immediate: !0 });
		function Ye(e, t) {
			Z.flags = t ? [...Z.flags.filter((t) => t !== e), e] : Z.flags.filter((t) => t !== e);
		}
		function Xe(e) {
			let t = Y.value.find((t) => t.code === e);
			return t ? l(t.labels, h.value, t.code) : e ?? "";
		}
		async function Ze() {
			let e = J.value;
			if (!U || !e) return;
			let t = {
				labels: p(e.labels, h.value, Z.label),
				parent: s(Z.parent),
				enabled: Z.enabled,
				external_ids: f(Q.value),
				...e.flags === void 0 ? {} : { flags: Z.flags }
			};
			await A.run(async () => i(await U.PATCH("/owners/{owner}", {
				params: { path: { owner: z.value } },
				body: a(t)
			}))) && await q();
		}
		async function Qe() {
			let e = J.value;
			if (!U || !e) return;
			let t = {
				code: $.code.trim(),
				type: $.type,
				parent: e.code
			};
			await I.run(async () => i(await U.POST("/owners", { body: a(t) }))) && ($.code = "", await q());
		}
		async function $e() {
			U && window.confirm(m("showroom.owners.confirm_delete")) && await A.run(async () => i(await U.DELETE("/owners/{owner}", { params: { path: { owner: z.value } } }))) && await v("/showroom/owners");
		}
		return (r, i) => {
			let a = x, o = oe, s = ne, c = re, u = te, d = b, f = y, p = ae, _ = ie, v = se, C = ee;
			return M(), E("div", de, [k(C, {
				"screen-key": "showroom.owner",
				record: R(z)
			}, {
				default: B(() => [k(t, {
					ready: !R(G) && R(J) !== null,
					failed: R(K),
					status: R(We)
				}, {
					default: B(() => [R(J) ? (M(), E("div", fe, [
						(R(J).chain ?? []).length > 1 ? (M(), E("nav", pe, [(M(!0), E(S, null, F(R(Ge), (e) => (M(), E(S, { key: e }, [k(a, {
							to: R(g)(e),
							class: "font-mono hover:underline"
						}, {
							default: B(() => [O(L(e), 1)]),
							_: 2
						}, 1032, ["to"]), k(o, {
							name: "i-lucide-chevron-right",
							class: "size-3"
						})], 64))), 128)), D("span", me, L(R(J).code), 1)])) : T("", !0),
						k(u, { overline: R(m)("showroom.owners.title") }, {
							title: B(() => [D("span", he, L(R(l)(R(J).labels, R(h), R(J).code)), 1)]),
							description: B(() => [D("span", ge, L([R(J).code, R(J).type].filter((e) => e).join(" · ")), 1)]),
							default: B(() => [
								k(s, {
									color: R(J).enabled ? "success" : "neutral",
									variant: "subtle",
									"data-testid": "showroom-owner-enabled"
								}, {
									default: B(() => [O(L(R(J).enabled ? R(m)("showroom.catalog.enabled") : R(m)("showroom.catalog.disabled")), 1)]),
									_: 1
								}, 8, ["color"]),
								k(c, {
									to: `/showroom/products?owner=${encodeURIComponent(R(J).code)}`,
									variant: "outline",
									icon: "i-lucide-package",
									"data-testid": "showroom-owner-products"
								}, {
									default: B(() => [O(L(R(m)("showroom.owners.products")), 1)]),
									_: 1
								}, 8, ["to"]),
								k(c, {
									color: "error",
									variant: "outline",
									icon: "i-lucide-trash-2",
									loading: R(A).busy.value,
									"data-testid": "showroom-owner-delete",
									onClick: $e
								}, {
									default: B(() => [O(L(R(m)("showroom.catalog.delete")), 1)]),
									_: 1
								}, 8, ["loading"])
							]),
							_: 1
						}, 8, ["overline"]),
						D("p", _e, L(R(m)("showroom.owners.counts", {
							products: R(J).products_count ?? 0,
							models: R(J).product_models_count ?? 0
						})), 1),
						D("form", {
							class: "space-y-5",
							"data-testid": "showroom-owner-form",
							onSubmit: V(Ze, ["prevent"])
						}, [
							k(e, { action: R(A) }, null, 8, ["action"]),
							k(_, null, {
								header: B(() => [D("h2", ve, L(R(m)("showroom.owners.details")), 1)]),
								default: B(() => [D("div", ye, [
									k(f, {
										label: R(m)("showroom.catalog.fields.label_in", { locale: R(h) }),
										error: R(A).error("labels")
									}, {
										default: B(() => [k(d, {
											modelValue: R(Z).label,
											"onUpdate:modelValue": i[0] ||= (e) => R(Z).label = e,
											class: "w-56",
											"data-testid": "showroom-owner-label"
										}, null, 8, ["modelValue"])]),
										_: 1
									}, 8, ["label", "error"]),
									k(f, {
										label: R(m)("showroom.catalog.fields.parent"),
										hint: R(m)("showroom.owners.move_hint"),
										error: R(A).error("parent")
									}, {
										default: B(() => [k(d, {
											modelValue: R(Z).parent,
											"onUpdate:modelValue": i[1] ||= (e) => R(Z).parent = e,
											class: "w-48",
											"data-testid": "showroom-owner-parent"
										}, null, 8, ["modelValue"])]),
										_: 1
									}, 8, [
										"label",
										"hint",
										"error"
									]),
									k(p, {
										modelValue: R(Z).enabled,
										"onUpdate:modelValue": i[2] ||= (e) => R(Z).enabled = e,
										label: R(m)("showroom.catalog.enabled"),
										class: "mt-field-label h-field items-center",
										"data-testid": "showroom-owner-enabled-input"
									}, null, 8, ["modelValue", "label"])
								])]),
								_: 1
							}),
							k(_, { "data-testid": "showroom-owner-identity" }, {
								header: B(() => [D("h2", be, L(R(m)("showroom.catalog.products.identifiers_and_flags")), 1)]),
								default: B(() => [D("div", xe, [
									D("p", Se, [D("span", Ce, L(R(m)("showroom.catalog.fields.slug")) + ":", 1), D("span", we, L(R(J).slug), 1)]),
									k(n, {
										modelValue: R(Q),
										"onUpdate:modelValue": i[3] ||= (e) => j(Q) ? Q.value = e : null,
										"synced-at": R(J).external_ids_synced_at ?? {},
										error: R(A).error
									}, null, 8, [
										"modelValue",
										"synced-at",
										"error"
									]),
									R(J).flags === void 0 ? T("", !0) : (M(), E("div", Te, [
										D("p", Ee, L(R(m)("showroom.catalog.flags.own")), 1),
										D("div", De, [(M(!0), E(S, null, F(R(X), (e) => (M(), w(p, {
											key: e.code,
											"model-value": R(Z).flags.includes(e.code),
											"data-testid": `showroom-flag-${e.code}`,
											"onUpdate:modelValue": (t) => Ye(e.code, t === !0)
										}, {
											label: B(() => [D("span", Oe, L(e.code), 1), R(l)(e.labels, R(h), e.code) === e.code ? T("", !0) : (M(), E("span", ke, L(R(l)(e.labels, R(h), e.code)), 1))]),
											_: 2
										}, 1032, [
											"model-value",
											"data-testid",
											"onUpdate:modelValue"
										]))), 128)), R(X).length === 0 ? (M(), E("span", Ae, L(R(m)("showroom.owners.no_flags")), 1)) : T("", !0)]),
										R(A).error("flags") ? (M(), E("small", je, L(R(A).error("flags")), 1)) : T("", !0)
									])),
									D("div", Me, [D("p", Ne, L(R(m)("showroom.catalog.flags.effective")), 1), D("ul", Pe, [(M(!0), E(S, null, F(R(J).effective_flags ?? [], (e) => (M(), E("li", {
										key: e,
										class: "flex items-center gap-1.5 text-sm",
										"data-effective-flag": e
									}, [k(s, {
										color: (R(J).flags ?? []).includes(e) ? "primary" : "neutral",
										variant: "subtle"
									}, {
										default: B(() => [O(L(e), 1)]),
										_: 2
									}, 1032, ["color"]), (R(J).flags ?? []).includes(e) ? T("", !0) : (M(), E("span", Ie, L(R(m)("showroom.catalog.inherited")), 1))], 8, Fe))), 128)), (R(J).effective_flags ?? []).length === 0 ? (M(), E("li", Le, L(R(m)("showroom.catalog.none")), 1)) : T("", !0)])])
								])]),
								_: 1
							}),
							D("div", null, [k(c, {
								type: "submit",
								icon: "i-lucide-check",
								loading: R(A).busy.value,
								"data-testid": "showroom-owner-save"
							}, {
								default: B(() => [O(L(R(m)("showroom.catalog.save")), 1)]),
								_: 1
							}, 8, ["loading"])])
						], 32),
						D("div", Re, [k(_, { "data-testid": "showroom-owner-children" }, {
							header: B(() => [D("h2", ze, L(R(m)("showroom.owners.children")), 1)]),
							default: B(() => [D("div", Be, [
								(R(J).children ?? []).length === 0 ? (M(), E("p", Ve, L(R(m)("showroom.owners.no_children")), 1)) : (M(), E("ul", He, [(M(!0), E(S, null, F(R(J).children, (e) => (M(), E("li", { key: e }, [k(a, {
									to: R(g)(e),
									class: "font-mono text-sm hover:underline",
									"data-testid": `showroom-owner-child-${e}`
								}, {
									default: B(() => [O(L(e), 1)]),
									_: 2
								}, 1032, ["to", "data-testid"])]))), 128))])),
								D("form", {
									class: "flex flex-wrap items-start gap-3",
									"data-testid": "showroom-owner-child-form",
									onSubmit: V(Qe, ["prevent"])
								}, [
									k(e, {
										action: R(I),
										class: "w-full"
									}, null, 8, ["action"]),
									k(f, {
										label: R(m)("showroom.catalog.fields.code"),
										error: R(I).error("code"),
										required: ""
									}, {
										default: B(() => [k(d, {
											modelValue: R($).code,
											"onUpdate:modelValue": i[4] ||= (e) => R($).code = e,
											required: "",
											class: "w-40",
											"data-testid": "showroom-owner-child-code"
										}, null, 8, ["modelValue"])]),
										_: 1
									}, 8, ["label", "error"]),
									k(f, {
										label: R(m)("showroom.catalog.fields.type"),
										error: R(I).error("type"),
										required: ""
									}, {
										default: B(() => [k(v, {
											modelValue: R($).type,
											"onUpdate:modelValue": i[5] ||= (e) => R($).type = e,
											items: R(qe),
											placeholder: R(m)("showroom.owners.choose_type"),
											required: "",
											class: "w-40",
											"data-testid": "showroom-owner-child-type"
										}, null, 8, [
											"modelValue",
											"items",
											"placeholder"
										])]),
										_: 1
									}, 8, ["label", "error"]),
									k(c, {
										type: "submit",
										icon: "i-lucide-plus",
										loading: R(I).busy.value,
										class: "mt-field-label",
										"data-testid": "showroom-owner-child-submit"
									}, {
										default: B(() => [O(L(R(m)("showroom.owners.add_child")), 1)]),
										_: 1
									}, 8, ["loading"])
								], 32),
								R(I).error("parent") ? (M(), E("small", Ue, L(R(I).error("parent")), 1)) : T("", !0)
							])]),
							_: 1
						}), k(ue, {
							assets: R(J).assets ?? [],
							owner: R(J).code,
							onChanged: R(q)
						}, null, 8, [
							"assets",
							"owner",
							"onChanged"
						])])
					])) : T("", !0)]),
					_: 1
				}, 8, [
					"ready",
					"failed",
					"status"
				])]),
				_: 1
			}, 8, ["record"])]);
		};
	}
});
export { K as default };
