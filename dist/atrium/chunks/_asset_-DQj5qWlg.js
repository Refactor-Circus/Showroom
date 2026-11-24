import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { t } from "./CatalogState-Bkly-MlG.js";
import { C as n, D as r, O as i, a, f as o, k as s } from "./catalog-DCoO3A8a.js";
import { u as c } from "./structure-DbqcM-iO.js";
import { c as l, o as ee, r as u, s as te, t as d } from "./assets-DNUkY-zI.js";
import { AtriumIntegrationScreen as f, AtriumState as ne, navigateTo as p } from "@refactor-circus/atrium";
import { UButton as re, UCard as ie, UFormField as ae, UInput as oe, ULink as se, UPageHeader as ce, USelect as le, UTable as m } from "@refactor-circus/atrium/ui";
import { computed as h, createCommentVNode as g, createElementBlock as _, createElementVNode as v, createTextVNode as y, createVNode as b, defineComponent as x, isRef as ue, openBlock as S, reactive as C, ref as de, shallowRef as fe, toDisplayString as w, unref as T, useTemplateRef as E, watch as D, withCtx as O, withModifiers as k } from "vue";
import { useI18n as A } from "vue-i18n";
import { useRoute as j } from "vue-router";
// src/pages/assets/[asset].vue?vue&type=script&setup=true&lang.ts
var M = {
	class: "space-y-4",
	"data-testid": "showroom-asset"
}, N = {
	key: 0,
	class: "space-y-5"
}, P = { "data-testid": "showroom-asset-title" }, F = { class: "grid gap-5 lg:grid-cols-3" }, I = ["src", "alt"], L = { class: "grid grid-cols-[auto_minmax(0,1fr)] gap-x-4 gap-y-1 text-sm" }, R = { class: "text-muted" }, z = { class: "font-mono" }, B = { class: "text-muted" }, V = { class: "text-muted" }, pe = {
	class: "tabular-nums",
	"data-testid": "showroom-asset-size"
}, me = { class: "text-muted" }, he = {
	class: "break-all font-mono text-xs",
	"data-testid": "showroom-asset-checksum"
}, ge = { class: "space-y-5 lg:col-span-2" }, _e = { class: "font-semibold" }, ve = { class: "font-semibold" }, ye = { class: "space-y-4" }, be = ["data-testid"], H = /* @__PURE__ */ x({
	__name: "[asset]",
	setup(x) {
		let { t: H, locale: U } = A(), xe = j(), W = i(), G = h(() => String(xe.params.asset)), { client: K, data: q, pending: Se, failed: Ce, status: we, reload: J } = s(async (e) => n(await e.GET("/assets/{asset}", { params: { path: { asset: G.value } } })).data, G), Y = h(() => q.value), Te = h(() => Y.value?.links ?? []), X = de(""), Z = fe(null), Q = E("fileInput"), Ee = h(() => d.map((e) => ({
			label: H(`showroom.assets.link_types.${e}`),
			value: e
		}))), De = h(() => [
			{
				accessorKey: "type",
				header: H("showroom.catalog.fields.type")
			},
			{
				accessorKey: "target",
				header: H("showroom.assets.target")
			},
			{
				accessorKey: "role",
				header: H("showroom.assets.role")
			},
			{
				id: "actions",
				header: "",
				meta: { class: { td: "text-right" } }
			}
		]), $ = C({
			type: "product",
			target: "",
			role: "media"
		});
		D(q, () => {
			X.value = Y.value?.labels[U.value] ?? "";
		}, { immediate: !0 });
		function Oe(e) {
			Z.value = e.target.files?.[0] ?? null;
		}
		async function ke() {
			let e = Y.value;
			if (!K || !e) return;
			let t = c(e.labels, U.value, X.value), i = Z.value;
			await W.run(async () => {
				if (!i) return n(await K.PATCH("/assets/{asset}", {
					params: { path: { asset: G.value } },
					body: r({ labels: t })
				}));
				let e = new FormData();
				return e.append("file", i), u(e, t), n(await K.PATCH("/assets/{asset}", {
					params: { path: { asset: G.value } },
					body: e,
					bodySerializer: (e) => e
				}));
			}) && (Z.value = null, Q.value?.inputRef && (Q.value.inputRef.value = ""), await J());
		}
		async function Ae() {
			if (!K) return;
			let e = a($.role), t = {
				type: $.type,
				target: $.target.trim(),
				...e ? { role: e } : {}
			};
			await W.run(async () => n(await K.POST("/assets/{asset}/links", {
				params: { path: { asset: G.value } },
				body: t
			}))) && ($.target = "", await J());
		}
		async function je(e) {
			if (!K) return;
			let t = {
				type: e.type,
				target: e.target,
				...e.role ? { role: e.role } : {}
			};
			await W.run(async () => n(await K.DELETE("/assets/{asset}/links", { params: {
				path: { asset: G.value },
				query: t
			} }))) && await J();
		}
		async function Me() {
			K && window.confirm(H("showroom.assets.confirm_delete")) && await W.run(async () => n(await K.DELETE("/assets/{asset}", { params: { path: { asset: G.value } } }))) && await p("/showroom/assets");
		}
		return (n, r) => {
			let i = re, a = ce, s = ie, c = oe, u = ae, d = ne, p = se, h = m, x = le, C = f;
			return S(), _("div", M, [b(C, {
				"screen-key": "showroom.asset",
				record: T(G)
			}, {
				default: O(() => [b(t, {
					ready: !T(Se) && T(Y) !== null,
					failed: T(Ce),
					status: T(we)
				}, {
					default: O(() => [T(Y) ? (S(), _("div", N, [
						b(a, {
							headline: T(H)("showroom.assets.title"),
							description: T(Y).filename,
							ui: { title: "truncate" }
						}, {
							title: O(() => [v("span", P, w(T(o)(T(Y).labels, T(U), T(Y).code)), 1)]),
							links: O(() => [b(i, {
								to: T(Y).url,
								target: "_blank",
								external: "",
								variant: "outline",
								icon: "i-lucide-external-link",
								"data-testid": "showroom-asset-open"
							}, {
								default: O(() => [y(w(T(H)("showroom.assets.open_file")), 1)]),
								_: 1
							}, 8, ["to"]), b(i, {
								color: "error",
								variant: "outline",
								icon: "i-lucide-trash-2",
								loading: T(W).busy.value,
								"data-testid": "showroom-asset-delete",
								onClick: Me
							}, {
								default: O(() => [y(w(T(H)("showroom.catalog.delete")), 1)]),
								_: 1
							}, 8, ["loading"])]),
							_: 1
						}, 8, ["headline", "description"]),
						b(e, { action: T(W) }, null, 8, ["action"]),
						v("div", F, [b(s, { "data-testid": "showroom-asset-file-card" }, {
							default: O(() => [T(te)(T(Y)) ? (S(), _("img", {
								key: 0,
								src: T(Y).url,
								alt: T(o)(T(Y).labels, T(U), T(Y).code),
								class: "mb-3 w-full rounded-md object-contain",
								"data-testid": "showroom-asset-image"
							}, null, 8, I)) : g("", !0), v("dl", L, [
								v("dt", R, w(T(H)("showroom.catalog.fields.code")), 1),
								v("dd", z, w(T(Y).code), 1),
								v("dt", B, w(T(H)("showroom.catalog.fields.type")), 1),
								v("dd", null, w(T(Y).mime_type ?? T(H)("showroom.catalog.none")), 1),
								v("dt", V, w(T(H)("showroom.assets.size")), 1),
								v("dd", pe, w(T(ee)(T(Y).size)), 1),
								v("dt", me, w(T(H)("showroom.assets.checksum")), 1),
								v("dd", he, w(T(Y).checksum ?? T(H)("showroom.catalog.none")), 1)
							])]),
							_: 1
						}), v("div", ge, [b(s, null, {
							header: O(() => [v("h2", _e, w(T(H)("showroom.assets.details")), 1)]),
							default: O(() => [v("form", {
								class: "flex flex-wrap items-end gap-3",
								"data-testid": "showroom-asset-form",
								onSubmit: k(ke, ["prevent"])
							}, [
								b(u, {
									label: T(H)("showroom.catalog.fields.label_in", { locale: T(U) }),
									error: T(W).error("labels")
								}, {
									default: O(() => [b(c, {
										modelValue: T(X),
										"onUpdate:modelValue": r[0] ||= (e) => ue(X) ? X.value = e : null,
										class: "w-64",
										"data-testid": "showroom-asset-label"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, ["label", "error"]),
								b(u, {
									label: T(H)("showroom.assets.replace_file"),
									error: T(W).error("file")
								}, {
									default: O(() => [b(c, {
										ref_key: "fileInput",
										ref: Q,
										type: "file",
										"data-testid": "showroom-asset-file",
										onChange: Oe
									}, null, 512)]),
									_: 1
								}, 8, ["label", "error"]),
								b(i, {
									type: "submit",
									icon: "i-lucide-check",
									loading: T(W).busy.value,
									"data-testid": "showroom-asset-save"
								}, {
									default: O(() => [y(w(T(H)("showroom.catalog.save")), 1)]),
									_: 1
								}, 8, ["loading"])
							], 32)]),
							_: 1
						}), b(s, { "data-testid": "showroom-asset-links" }, {
							header: O(() => [v("h2", ve, w(T(H)("showroom.assets.links")), 1)]),
							default: O(() => [v("div", ye, [b(h, {
								data: T(Te),
								columns: T(De)
							}, {
								empty: O(() => [b(d, {
									compact: "",
									title: T(H)("showroom.assets.no_links"),
									"data-testid": "showroom-asset-links-empty"
								}, null, 8, ["title"])]),
								"type-cell": O(({ row: { original: e } }) => [v("span", { "data-testid": `showroom-asset-link-${e.type}-${e.target}` }, w(T(H)(`showroom.assets.link_types.${e.type}`)), 9, be)]),
								"target-cell": O(({ row: { original: e } }) => [b(p, {
									to: T(l)(e),
									class: "font-mono text-xs hover:underline"
								}, {
									default: O(() => [y(w(e.target), 1)]),
									_: 2
								}, 1032, ["to"])]),
								"role-cell": O(({ row: { original: e } }) => [y(w(e.role ?? T(H)("showroom.catalog.none")), 1)]),
								"actions-cell": O(({ row: { original: e } }) => [b(i, {
									variant: "ghost",
									color: "neutral",
									size: "sm",
									icon: "i-lucide-unlink",
									loading: T(W).busy.value,
									"data-testid": `showroom-asset-unlink-${e.type}-${e.target}`,
									onClick: (t) => je(e)
								}, {
									default: O(() => [y(w(T(H)("showroom.assets.unlink")), 1)]),
									_: 1
								}, 8, [
									"loading",
									"data-testid",
									"onClick"
								])]),
								_: 1
							}, 8, ["data", "columns"]), v("form", {
								class: "flex flex-wrap items-end gap-3",
								"data-testid": "showroom-asset-link-form",
								onSubmit: k(Ae, ["prevent"])
							}, [
								b(u, {
									label: T(H)("showroom.catalog.fields.type"),
									error: T(W).error("type"),
									required: ""
								}, {
									default: O(() => [b(x, {
										modelValue: T($).type,
										"onUpdate:modelValue": r[1] ||= (e) => T($).type = e,
										items: T(Ee),
										class: "w-40",
										"data-testid": "showroom-asset-link-type"
									}, null, 8, ["modelValue", "items"])]),
									_: 1
								}, 8, ["label", "error"]),
								b(u, {
									label: T(H)("showroom.assets.target"),
									hint: T(H)("showroom.assets.target_hint"),
									error: T(W).error("target"),
									required: ""
								}, {
									default: O(() => [b(c, {
										modelValue: T($).target,
										"onUpdate:modelValue": r[2] ||= (e) => T($).target = e,
										required: "",
										class: "w-48",
										"data-testid": "showroom-asset-link-target"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, [
									"label",
									"hint",
									"error"
								]),
								b(u, {
									label: T(H)("showroom.assets.role"),
									error: T(W).error("role")
								}, {
									default: O(() => [b(c, {
										modelValue: T($).role,
										"onUpdate:modelValue": r[3] ||= (e) => T($).role = e,
										class: "w-36",
										"data-testid": "showroom-asset-link-role"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, ["label", "error"]),
								b(i, {
									type: "submit",
									icon: "i-lucide-link",
									loading: T(W).busy.value,
									"data-testid": "showroom-asset-link-submit"
								}, {
									default: O(() => [y(w(T(H)("showroom.assets.link")), 1)]),
									_: 1
								}, 8, ["loading"])
							], 32)])]),
							_: 1
						})])])
					])) : g("", !0)]),
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
export { H as default };
